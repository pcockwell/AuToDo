<?php

namespace Autodo\Support;

class InputConverter {
    
    public static function convertToObject($input)
    {
        $new_input = array();

        foreach($input as $key => $content)
        {
            $class_type = str_singular(studly_case($key));
            if (class_exists($class_type))
            {
                $class = new \ReflectionClass($class_type);
                $class_name = $class->getShortName();
                if (is_array($content))
                {
                    $single_item = false;
                    foreach($content as $content_item)
                    {
                        if (!is_array($content_item))
                        {
                            $single_item = true;
                            break;
                        }
                        try
                        {
                            $new_input[$class_name][] = $class->newInstance($content_item);
                        }
                        catch (ValidationException $v)
                        {
                            $errors['errors'] = array_merge($errors['errors'], $v->get());
                        }
                    }

                    if ($single_item)
                    {
                        try
                        {
                            $new_input[$class_name] = $class->newInstance($content);
                        }
                        catch (ValidationException $v)
                        {
                            $errors['errors'] = array_merge($errors['errors'], $v->get());
                        }
                        catch (ErrorException $e)
                        {
                            $errors['errors'][] = 'Invalid content supplied for ' . $class_name . ' object';
                        }
                    }
                }
                else
                {
                    $errors['errors'][] = 'Invalid content supplied for ' . $class_name . ' object';
                }
            }
            else
            {
                $new_input[$key] = $content;
            }
        }

        return $new_input;
    }

}
