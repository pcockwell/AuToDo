<?php

namespace Autodo\GCal;

use Carbon\Carbon;
use \FixedEvent;

class Parser {

  const DAYS_IN_WEEK = 7;

  // This function parses a json string from the events list call of the
  // Google Calendar API. It currently returns an array of attribute arrays.
  // Each attribute array can be used to construct a FixedEvent object.
  //
  // Special cases:
  // 1) Some GCal events have a recurrence that never ends. In this case, since
  // the FixedEvent object validation does not allow a null value for the
  // 'end_date' attribute, the function returns a Carbon object corresponding
  // to the epoch in UTC timezone.
  // 2) The attributes break_before and break_after are always given as 0 value
  // since there isn't a way to specify a break time from an event on GCal
  //
  // TODO: Remove the hardcoded string when the function is called using real
  // data.
  public static function parseEventsList($eventsList) 
  {
    
    $DAYS_OF_WEEK = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');

    $items = array();

    foreach ($eventsList as $item_data) {
      //$item_data = json_decode($item_match[0], true);
      
      // Recurrence variables
      if (property_exists($item_data, 'recurrence')) {
        $recurrence_str = $item_data->recurrence[0];
  
        // Get the frequency string
        $matched = preg_match('/(?<=FREQ=)[^;]*?(?=(;|$))/',
                              $recurrence_str, $match_buffer);
        assert($matched !== false);
        if ($matched === 1) {
          $frequency_str = $match_buffer[0];
        } else {
          $frequency_str = null;
        }

        // Get the until string
        $matched = preg_match('/(?<=UNTIL=)[^;]*?(?=(;|$))/',
                              $recurrence_str, $match_buffer);
        assert($matched !== false);
        if ($matched === 1) {
          $until_str = $match_buffer[0];
        } else {
          $until_str = null;
        }

        // Get the count string
        $matched = preg_match('/(?<=COUNT=)[^;]*?(?=(;|$))/',
                              $recurrence_str, $match_buffer);
        assert($matched !== false);
        if ($matched === 1) {
          $count = intval($match_buffer[0]);
        } else {
          $count = null;
        }

        // Get the by_day string, which is a list of days in a week.
        // dow_ref = day of week reference, which is a number followed by a day.
        // e.g. 1FR means first Friday of each month.
        // For any case, at most one of $by_day or $dow_ref will be populated.
        // They could also both be null.
        $matched = preg_match('/(?<=BYDAY=)[^;]*?(?=(;|$))/',
                              $recurrence_str, $match_buffer);
        assert($matched !== false);
        if ($matched === 1) {
          if (is_numeric($match_buffer[0][0])) {
            // This field is an indicator for the day of week recurrence for
            // the monthly frequency.
            $dow_ref = $match_buffer[0];
            $by_day = null;
          } else {
            $by_day = preg_split('/,/', $match_buffer[0], -1,
                                 PREG_SPLIT_NO_EMPTY);
            foreach ($by_day as &$day) {
              for ($i = 0; $i < self::DAYS_IN_WEEK; ++$i) {
                if ($DAYS_OF_WEEK[$i] == $day) {
                  $day = $i;
                  break;
                }
              }
            }
            $dow_ref = null;
          }
        } else {
          $by_day = null;
          $dow_ref = null;
        }

        // Get the interval string
        $matched = preg_match('/(?<=INTERVAL=)[^;]*?(?=(;|$))/',
                              $recurrence_str, $match_buffer);
        assert($matched !== false);
        if ($matched === 1) {
          $interval = intval($match_buffer[0]);
        } else {
          $interval = 1;
        }
      }

      $start_datetime = new Carbon($item_data->start->dateTime);
      $end_datetime = new Carbon($item_data->end->dateTime);
      $repeat_until_never = false;

      // Create the recurrence json string.
      if (isset($by_day)) {
        $recurrence = '[';
        for ($i = 0; $i < count($by_day); ++$i) {
          if ($i > 0) {
            $recurrence .= ',';
          }
          $recurrence .= strval($by_day[$i]);
        }
        $recurrence .= ']';
      }

      // This whole next section deals with advancing $end_datetime to the
      // appropriate day to match our FixedEvent data structure.
      if (isset($until_str)) { // Recurrence happens until a specified date.
        $end_datetime = new Carbon($until_str);
      } else if (isset($count)) { // Recurrence happens for $count times.
        assert(isset($frequency_str)); // Frequency must be specified.
        switch($frequency_str) {
          case 'DAILY':
            // Event happens every day, so just advance by number of times it's
            // scheduled.
            $end_datetime->addDays($count);
            break;
          case 'WEEKLY':
            // Event happens weekly.
            // If it occurs 5 times a week, then each week will fulfill 5 times
            // of the $count amount of recurrences.
            assert(isset($by_day));
            assert(count($by_day) > 0);
            // Find which week of the day the first occurence happens.
            for ($i = 0; $i < count($by_day); ++$i) {
              if ($end_datetime->dayOfWeek == $by_day[$i]) {
                $start_dow_idx = $i;
                $event_times_this_week = count($by_day) - $i;
                break;
              }
              assert($i != count($by_day-1));
            }

            // Align the recurrence count to the end of the current week.
            $days_to_add = 0;
            if ($event_times_this_week < $count) {
              $days_to_add +=
                  $by_day[count($by_day)-1] - $by_day[$start_dow_idx];
              $count -= $event_times_this_week;
            } else {
              $days_to_add +=
                  $by_day[$start_dow_idx+$count-1] - $by_day[$start_dow_idx];
              $count = 0; 
            }

            // Loop to decrement by week (works since we just aligned).
            // Takes care of the last week as well.
            while ($count > 0) {
              if ($count < count($by_day)) {
                $days_to_add += $by_day[$count-1] + 1;
                $count = 0;
              } else {
                $days_to_add += self::DAYS_IN_WEEK;
                $count -= count($by_day);
              }
            }

            // Update the end date.
            $end_datetime->addDays($days_to_add);
            break;
          case 'MONTHLY':
            // Events happen monthly with some interval (e.g. every 2 months).
            // Just add the appropriate number of months.
            $end_datetime->addMonths($interval*($count-1));
            if (!isset($by_day)) {
              // This is a special case of monthly recurrence where events
              // happen by the day of week in the month (e.g. first Friday).
              // This should be specified by the $by_day value (e.g. 1FR).
              $week_number = intval($dow_ref[0]);
              $day_of_week = substr($dow_ref, 1);
              for ($i = 0; $i < self::DAYS_IN_WEEK; ++$i) {
                if ($day_of_week == $DAYS_OF_WEEK[$i]) {
                  $day_of_week = $i;
                  break;
                }
              }
              assert(strlen($day_of_week) == 2);
              // Set to correct week, then advance to correct day of week.
              $end_datetime->day = $week_number * self::DAYS_IN_WEEK + 1;
              if ($end_datetime->dayOfWeek > $day_of_week) {
                $end_datetime->addDays(
                    self::DAYS_IN_WEEK - $end_datetime->dayOfWeek + $day_of_week);
              } else if ($end_datetime->dayOfWeek < $day_of_week) {
                $end_datetime->addDays($day_of_week - $end_datetime->dayOfWeek);
              }
            }
            break;
          case 'YEARLY':
            // Events happen yearly with some interval (e.g. every 2 years).
            // Just add the appropriate number of years.
            $end_datetime->addYears($interval*($count-1));
            break;
          default:
            // Should never happen
            assert(false);
        }
      } else { // Recurrence goes on forever.
        $repeat_until_never = true;
      }

      // Create array of FixedEvent objects to return to caller.
      $items[] = new FixedEvent(array(
          'name' => $item_data->summary,
          'start_time' => 60*$start_datetime->hour + $start_datetime->minute,
          'end_time' => 60*$end_datetime->hour + $end_datetime->minute,
          'start_date' => $start_datetime->copy()->startOfDay(),
          'end_date' => $repeat_until_never ?
              Carbon::createFromTimeStampUTC(-1) :
              $end_datetime->copy()->startOfDay(),
          'recurrences' => isset($recurrence) ? $recurrence : '[]',
          'break_before' => 0,
          'break_after' => 0));
    }

    return $items;
  }

}
