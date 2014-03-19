<?php
/**
 * Created by PhpStorm.
 * User: tonzhang
 * Date: 3/19/14
 * Time: 2:03 PM
 */

class DependencyGraph {

    protected $fillable = array('dependencies');

    protected static $rules = array(
        'dependencies' => array('required'),
    );


    public function __construct($attributes = array(), $exists = false)
    {
        if (count($attributes) > 0) {

            $validator = Validator::make($attributes, self::$rules);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }
        parent::__construct($attributes, $exists);

    }

    public function remove_cycles() {
        return;
    }

    public function getStraightArr() {

    }

} 