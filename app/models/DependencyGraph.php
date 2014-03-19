<?php
/**
 * Created by PhpStorm.
 * User: tonzhang
 * Date: 3/19/14
 * Time: 2:03 PM
 */

use Autodo\Exception\ValidationException;

class DependencyGraph extends Eloquent {

    protected $fillable = array('dependencies');

    protected static $rules = array(
        'dependencies' => array('required'),
    );

    protected $graph;


    public function __construct($attributes = array(), $exists = false)
    {
        parent::__construct($attributes, $exists);
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
      print_r("reversed graph:<br />");
      print_r($this->graph);
      print_r("<br /><br />");
    }

    public function mergeableTaskList($tasks) {
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

      // Start a separate array with each root.
      $roots = array();
      foreach ($task_tracker as $task_name => $not_root) {
        if($not_root == 0) {
          $roots[$task_name] = array($task_name);
        }
      }

      print_r("roots:<br />");
      print_r($roots);
      print_r("<br /><br />");
      
    }
} 
