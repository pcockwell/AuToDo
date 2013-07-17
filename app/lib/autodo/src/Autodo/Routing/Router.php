<?php

namespace Autodo\Routing;

use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Routing\Route;

class Router extends BaseRouter
{
    /**
     * Create a new route instance.
     *
     * @param  string  $method
     * @param  string  $pattern
     * @param  mixed   $action
     * @return \Illuminate\Routing\Route
     */
    protected function createRoute($method, $pattern, $action)
    {
        // We will force the action parameters to be an array just for convenience.
        // This will let us examine it for other attributes like middlewares or
        // a specific HTTP schemes the route only responds to, such as HTTPS.
        if ( ! is_array($action))
        {
            $action = $this->parseAction($action);
        }
        
        $groupCount = count($this->groupStack);

        // If there are attributes being grouped across routes we will merge those
        // attributes into the action array so that they will get shared across
        // the routes. The route can override the attribute by specifying it.
        if ($groupCount > 0)
        {
            $index = $groupCount - 1;

            $action = $this->mergeGroup($action, $index);
        }

        // Next we will parse the pattern and add any specified prefix to the it so
        // a common URI prefix may be specified for a group of routes easily and
        // without having to specify them all for every route that is defined.
        list($pattern, $optional) = $this->getOptional($pattern);

        if (isset($action['prefix']))
        {
            $prefix = $action['prefix'];

            $pattern = $this->addPrefix($pattern, $prefix);
        }

        // We will create the routes, setting the Closure callbacks on the instance
        // so we can easily access it later. If there are other parameters on a
        // routes we'll also set those requirements as well such as defaults.
        $route = with(new Route($pattern))->setOptions(array(

            'compiler_class' => 'Autodo\\Routing\\RouteCompiler',
            '_call' => $this->getCallback($action),

        ))->setRouter($this)->addRequirements($this->patterns);

        if (isset($action['suffix']))
        {
            $suffix = $action['suffix'];

            $route->setOption('_suffix', $suffix);
        }

        $route->setRequirement('_method', $method);

        // Once we have created the route, we will add them to our route collection
        // which contains all the other routes and is used to match on incoming
        // URL and their appropriate route destination and on URL generation.
        $this->setAttributes($route, $action, $optional);

        $name = $this->getName($method, $pattern, $action);

        $this->routes->add($name, $route);

        return $route;
    }

    /**
     * Merge the current group stack into a given action.
     *
     * @param  array  $action
     * @param  int    $index
     * @return array
     */
    protected function mergeGroup($action, $index)
    {
        $prefix = $this->mergeGroupPrefix($action);
        $suffix = $this->mergeGroupSuffix($action);

        $action = array_merge_recursive($this->groupStack[$index], $action);

        // If we have a prefix, we will override the merged prefix with this correctly
        // concatenated one since prefixes shouldn't merge like the other groupable
        // attributes on the action. Then we can return this final merged arrays.
        if ($prefix != '') $action['prefix'] = $prefix;

        // If we have a suffix, we will override the merged suffix with this correctly
        // concatenated one since suffixes shouldn't merge like the other groupable
        // attributes on the action. Then we can return this final merged arrays.
        if ($suffix != '') $action['suffix'] = $suffix;

        return $action;
    }

    /**
     * Get the full group suffix for the current stack.
     *
     * @return string
     */
    protected function getGroupSuffix()
    {
        if (count($this->groupStack) > 0)
        {
            $group = $this->groupStack[count($this->groupStack) - 1];

            if (isset($group['suffix']))
            {
                return $group['suffix'];
            }
        }

        return '';
    }

    /**
     * Get the fully merged prefix for a given action.
     *
     * @param  array   $action
     * @return string
     */
    protected function mergeGroupSuffix($action)
    {
        $suffix = isset($action['suffix']) ? $action['suffix'] : '';

        $groupSuffix = $this->getGroupSuffix();
        if (!is_array($groupSuffix))
        {
            return trim($suffix.'/'.$groupSuffix, '/');
        }
        else
        {
            $mergedSuffixes = array();
            foreach($groupSuffix as $currentSuffix)
            {
                $mergedSuffixes[] = trim($suffix.'/'.$currentSuffix, '/');
            }
            return $mergedSuffixes;
        }

    }
}
