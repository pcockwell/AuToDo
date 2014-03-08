<?php

namespace Autodo\GCal;

use Carbon\Carbon;
use \FixedEvent;

class Parser {

  // TODO (oscar): note about time zones

  public static function parseEventsList($json_string) {
    $json_string = '{
"kind": "calendar#events",
"etag": "\"-kteSF26GsdKQ5bfmcd4H3_-u3g/HGuxAaHiTkL-bpcvzX1VEf2dV2A\"",
"summary": "test",
"description": "",
"updated": "2014-03-05T17:48:43.970Z",
"timeZone": "America/Toronto",
"accessRole": "owner",
"items": [
{
"kind": "calendar#event",
"etag": "\"-kteSF26GsdKQ5bfmcd4H3_-u3g/MTM5NDAwNzI5MDkwMzAwMA\"",
"id": "sqata4pglfh7apgo6foema7dd0",
"status": "confirmed",
"htmlLink": "https://www.google.com/calendar/event?eid=c3FhdGE0cGdsZmg3YXBnbzZmb2VtYTdkZDAgZDUwNjFycHFvZ2JsNzZrcDFidGxubTlybjhAZw",
"created": "2014-03-05T08:14:26.000Z",
"updated": "2014-03-05T08:14:50.903Z",
"summary": "event2",
"creator": {
"email": "oscarchow51510@gmail.com",
"displayName": "Oscar Chow"
},
"organizer": {
"email": "d5061rpqogbl76kp1btlnm9rn8@group.calendar.google.com",
"displayName": "test",
"self": true
},
"start": {
"dateTime": "2014-03-07T12:30:00-05:00"
},
"end": {
"dateTime": "2014-03-07T13:30:00-05:00"
},
"iCalUID": "sqata4pglfh7apgo6foema7dd0@google.com",
"sequence": 1,
"reminders": {
"useDefault": true
}
},
{
"kind": "calendar#event",
"etag": "\"-kteSF26GsdKQ5bfmcd4H3_-u3g/MTM5NDAwODMzMDM0NDAwMA\"",
"id": "r456rki9agbg9mrm4121djfu5s",
"status": "confirmed",
"htmlLink": "https://www.google.com/calendar/event?eid=cjQ1NnJraTlhZ2JnOW1ybTQxMjFkamZ1NXNfMjAxNDAzMDVUMDkwMDAwWiBkNTA2MXJwcW9nYmw3NmtwMWJ0bG5tOXJuOEBn",
"created": "2014-03-05T08:32:10.000Z",
"updated": "2014-03-05T08:32:10.344Z",
"summary": "event3",
"creator": {
"email": "oscarchow51510@gmail.com",
"displayName": "Oscar Chow"
},
"organizer": {
"email": "d5061rpqogbl76kp1btlnm9rn8@group.calendar.google.com",
"displayName": "test",
"self": true
},
"start": {
"dateTime": "2014-03-05T04:00:00-05:00",
"timeZone": "America/Toronto"
},
"end": {
"dateTime": "2014-03-05T05:00:00-05:00",
"timeZone": "America/Toronto"
},
"recurrence": [
"RRULE:FREQ=WEEKLY;UNTIL=20140409T080000Z;BYDAY=WE"
],
"iCalUID": "r456rki9agbg9mrm4121djfu5s@google.com",
"sequence": 0,
"reminders": {
"useDefault": true
}
},
{
"kind": "calendar#event",
"etag": "\"-kteSF26GsdKQ5bfmcd4H3_-u3g/MTM5NDAwODM1ODg0MDAwMA\"",
"id": "5dgdj28ft764o2dif78tra9h08",
"status": "confirmed",
"htmlLink": "https://www.google.com/calendar/event?eid=NWRnZGoyOGZ0NzY0bzJkaWY3OHRyYTloMDhfMjAxNDAzMDdUMTAzMDAwWiBkNTA2MXJwcW9nYmw3NmtwMWJ0bG5tOXJuOEBn",
"created": "2014-03-05T08:32:38.000Z",
"updated": "2014-03-05T08:32:38.840Z",
"summary": "event4",
"creator": {
"email": "oscarchow51510@gmail.com",
"displayName": "Oscar Chow"
},
"organizer": {
"email": "d5061rpqogbl76kp1btlnm9rn8@group.calendar.google.com",
"displayName": "test",
"self": true
},
"start": {
"dateTime": "2014-03-07T05:30:00-05:00",
"timeZone": "America/Toronto"
},
"end": {
"dateTime": "2014-03-07T06:30:00-05:00",
"timeZone": "America/Toronto"
},
"recurrence": [
"RRULE:FREQ=WEEKLY;COUNT=10;BYDAY=FR"
],
"iCalUID": "5dgdj28ft764o2dif78tra9h08@google.com",
"sequence": 0,
"reminders": {
"useDefault": true
}
},
{
"kind": "calendar#event",
"etag": "\"-kteSF26GsdKQ5bfmcd4H3_-u3g/MTM5NDA0MTc0MjQzODAwMA\"",
"id": "i497nl9mkefls4ovn939i1boqg",
"status": "confirmed",
"htmlLink": "https://www.google.com/calendar/event?eid=aTQ5N25sOW1rZWZsczRvdm45MzlpMWJvcWdfMjAxNDAzMDZUMDgzMDAwWiBkNTA2MXJwcW9nYmw3NmtwMWJ0bG5tOXJuOEBn",
"created": "2014-03-05T08:13:35.000Z",
"updated": "2014-03-05T17:49:02.438Z",
"summary": "event1",
"creator": {
"email": "oscarchow51510@gmail.com",
"displayName": "Oscar Chow"
},
"organizer": {
"email": "d5061rpqogbl76kp1btlnm9rn8@group.calendar.google.com",
"displayName": "test",
"self": true
},
"start": {
"dateTime": "2014-03-06T03:30:00-05:00",
"timeZone": "America/Toronto"
},
"end": {
"dateTime": "2014-03-06T04:30:00-05:00",
"timeZone": "America/Toronto"
},
"recurrence": [
"RRULE:FREQ=WEEKLY;BYDAY=SU,MO,TU,WE,TH,FR,SA"
],
"iCalUID": "i497nl9mkefls4ovn939i1boqg@google.com",
"sequence": 2,
"reminders": {
"useDefault": true
}
}
]
}';

    $DAYS_OF_WEEK = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
    assert(define('NUM_DAYS_IN_WEEK', 7));

    $items = array();

    // Get the bracket indicating the start of the items array
    $items_start_pos = strpos($json_string, '"items\"');
    $items_start_pos = strpos($json_string, '[', $items_start_pos+1);

    // Get the end of the items array
    $str = substr($json_string, $items_start_pos);
    $str = str_replace("\n", "", $str);
    assert(preg_match('/\[.*\]/', $str, $match));
//     print_r("size = " . count($match) . "<br />");
//     print_r($match);
//     print_r("<br /><br />");

    $items_array_str = $match[0];
    // Test phrase for the recursive regex
//     preg_match_all('/\{([^{}]*|(?R))*\}/', '{1a{2b{3c{4d}5e}}6f}{7g{8h}9i}{}', $items_match, PREG_SET_ORDER);
    preg_match_all('/\{([^{}]*|(?R))*\}/', $items_array_str, $items_match, PREG_SET_ORDER);
//     print_r("size = " . count($items_match) . "<br />");
//     print_r($items_match);
//     print_r("<br />");

    // Each element of $items_match is an array such that the matched string
    // is at index 0 of the inner array. For example, for 3 matched strings,
    // count($items_match) == 3 and matched strings are located at
    // $items_match[0][0], $items_match[1][0], and $items_match[2][0].
    foreach ($items_match as $item_match) {
      $item_data = json_decode($item_match[0], true);
      print_r($item_data);
      print_r("<br /><br />");
      
      // Recurrence variables
      if (array_key_exists('recurrence', $item_data)) {
        $recurrence_str = $item_data['recurrence'][0];
  
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
              for ($i = 0; $i < NUM_DAYS_IN_WEEK; ++$i) {
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

      $start_datetime = new Carbon($item_data['start']['dateTime']);
      $end_datetime = new Carbon($item_data['end']['dateTime']);
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
            }

            // Align the recurrence count to the end of the current week.
            $days_to_add = 0;
            if ($event_times_this_week >= $count) {
              // Need to use day of week to make calculation since
              // days of week isnt a contguous block
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
                $days_to_add += NUM_DAYS_IN_WEEK;
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
              for ($i = 0; $i < NUM_DAYS_IN_WEEK; ++$i) {
                if ($day_of_week == $DAYS_OF_WEEK[$i]) {
                  $day_of_week = $i;
                  break;
                }
              }
              assert(strlen($day_of_week) == 2);
              // Set to correct week, then advance to correct day of week.
              $end_datetime->day = $week_number * NUM_DAYS_IN_WEEK + 1;
              if ($end_datetime->dayOfWeek > $day_of_week) {
                $end_datetime->addDays(
                    NUM_DAYS_IN_WEEK - $end_datetime->dayOfWeek + $day_of_week);
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
          'name' => $item_data['summary'],
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
