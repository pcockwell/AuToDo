<?php
/**
 * Created by PhpStorm.
 * User: tonzhang
 * Date: 3/19/14
 * Time: 2:03 PM
 */

class DependencyGraph extends Eloquent
{

    protected $fillable = array('dependencies');

    protected $graph;
    protected $tasks;

    public function __construct($attributes, $exists = false)
    {
        parent::__construct(array('dependencies' => $attributes), $exists);
        self::constructGraph();
    }

    private function constructGraph() {
      $this->graph = array();
      foreach ($this->dependencies as $task_name => $deps) {
        foreach ($deps as $dep_task_name) {
          if(!array_key_exists($dep_task_name, $this->graph)) {
            $this->graph[$dep_task_name] = array();
          }
          $this->graph[$dep_task_name][] = $task_name;
        }
      }
    }

    public function depFreeTasks($tasks) {
      $dep_free_tasks = array();
      foreach ($tasks as $task_name => $task) {
        if (!array_key_exists($task_name, $this->dependencies) &&
            !array_key_exists($task_name, $this->graph)) {
          $dep_free_tasks[] = $task;
        }
      }
      return $dep_free_tasks;
    }

    public function sortTasks($tasks) {
      $this->tasks = $tasks;

      // Find roots of graph.
      $task_tracker = array();
      foreach ($this->dependencies as $task_name => $deps) {
        if(!array_key_exists($task_name, $task_tracker)) {
          $task_tracker[$task_name] = 0;
        }
        $task_tracker[$task_name] = 1;
        
        foreach ($deps as $dep_task_name) {
          if(!array_key_exists($dep_task_name, $task_tracker)) {
            $task_tracker[$dep_task_name] = 0;
          }
        }
      }

      $queue = array();
      foreach ($task_tracker as $task_name => $not_root) {
        if($not_root == 0) {
          self::insertInOrder($queue, $task_name);
          $task_tracker[$task_name] = 0;
        }
      }
  
      $sorted_tasks = array();
      $task_tracker = array();
      foreach ($queue as $task_name) {
        $task_tracker[$task_name] = null;
      }

      while (!empty($queue)) {
        $curr_task_name = $queue[0];
        array_splice($queue, 0, 1);
        $sorted_tasks[] = $curr_task_name;
        $task_tracker[$curr_task_name] = 1;

        if (array_key_exists($curr_task_name, $this->graph)) {
          foreach ($this->graph[$curr_task_name] as $neighbour_name) {
            if (!self::depSatisfied($task_tracker, $neighbour_name)) {
              continue;
            }
            if (!array_key_exists($neighbour_name, $task_tracker)) {
              self::insertInOrder($queue, $neighbour_name);
              $task_tracker[$neighbour_name] = 0;
            }
          }
        }
      }

      return $sorted_tasks;
    }

    public function insertInOrder(&$list, $ele) {
      for ($i = 0; $i < count($list); ++$i) {
        if ($list[$i] == $ele) {
          return;
        } else if ($this->tasks[$list[$i]]->due->gt($this->tasks[$ele]->due)) {
          array_splice($list, $i, 0, $ele);
          return;
        }
      }
      $list[] = $ele;
    }

    public function depSatisfied($task_tracker, $task_name) {
      if(!array_key_exists($task_name, $this->dependencies)) {
        return true;
      }
      foreach ($this->dependencies[$task_name] as $dep_task_name) {
        if(!array_key_exists($dep_task_name, $task_tracker) ||
           $task_tracker[$dep_task_name] == 0) {
          return false;
        }
      }
      return true;
    }
} 
