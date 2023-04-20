<?php

if (! class_exists('DummyWPActionsStore')) {
    class DummyWPActionsStore
    {
        private static $actions = [];

        private static $actionExecutionCount = [];

        public static function addAction($tag, $callback, $priority = 10, $acceptedArgs = 1)
        {
            self::$actions[$tag][] = [
                'callback' => $callback,
                'priority' => $priority,
                'accepted_args' => $acceptedArgs,
            ];

            return true;
        }

        public static function getActions()
        {
            return self::$actions;
        }

        public static function doAction($tag)
        {
            if (! isset(self::$actionExecutionCount[$tag])) {
                self::$actionExecutionCount[$tag] = 0;
            }

            return self::$actionExecutionCount[$tag] += 1;
        }

        public static function hasAction($tag, $args)
        {
            $has = isset(self::$actions[$tag]);

            if (! $has) {
                return false;
            }

            foreach (self::$actions[$tag] as $action) {
                if ($action['callback'] === $args['callback']
                    && $action['priority'] === $args['priority']
                    && $action['accepted_args'] === $args['accepted_args']
                ) {
                    return true;
                }
            }
        }

        public static function didAction($tag)
        {
            if (! isset(self::$actionExecutionCount[$tag])) {
                return false;
            }

            return self::$actionExecutionCount[$tag] > 0;
        }
    }
}

function add_action($tag, $callback, $priority = 10, $acceptedArgs = 1)
{
    return DummyWPActionsStore::addAction($tag, $callback, $priority, $acceptedArgs);
}

function do_action($tag, $arg = '')
{
    return DummyWPActionsStore::doAction($tag);
}
