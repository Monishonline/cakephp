<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Core\App;
use Cake\Routing\Exception\MissingControllerException;
use Cake\Utility\Inflector;
use ReflectionClass;

/**
 * Factory method for building controllers from request/response pairs.
 */
class ControllerFactory
{
    /**
     * Create a controller for a given request/response
     *
     * @param \Cake\Http\ServerRequest $request The request to build a controller for.
     * @param \Cake\Network\Response $response The response to use.
     * @return \Cake\Controller\Controller
     */
    public function create(ServerRequest $request, Response $response)
    {
        $pluginPath = $controller = null;
        $namespace = 'Controller';
        if ($request->getParam('plugin')) {
            $pluginPath = $request->getParam('plugin') . '.';
        }
        if ($request->getParam('controller')) {
            $controller = $request->getParam('controller');
        }
        if ($request->getParam('prefix')) {
            if (strpos($request->getParam('prefix'), '/') === false) {
                $namespace .= '/' . Inflector::camelize($request->getParam('prefix'));
            } else {
                $prefixes = array_map(
                    'Cake\Utility\Inflector::camelize',
                    explode('/', $request->getParam('prefix'))
                );
                $namespace .= '/' . implode('/', $prefixes);
            }
        }
        $firstChar = substr($controller, 0, 1);

        // Disallow plugin short forms, / and \\ from
        // controller names as they allow direct references to
        // be created.
        if (strpos($controller, '\\') !== false ||
            strpos($controller, '/') !== false ||
            strpos($controller, '.') !== false ||
            $firstChar === strtolower($firstChar)
        ) {
            $this->missingController($request);
        }

        $className = App::className($pluginPath . $controller, $namespace, 'Controller');
        if (!$className) {
            $this->missingController($request);
        }
        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract() || $reflection->isInterface()) {
            $this->missingController($request);
        }

        return $reflection->newInstance($request, $response, $controller);
    }

    /**
     * Throws an exception when a controller is missing.
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @throws \Cake\Routing\Exception\MissingControllerException
     * @return void
     */
    protected function missingController($request)
    {
        throw new MissingControllerException([
            'class' => $request->getParam('controller'),
            'plugin' => $request->getParam('plugin'),
            'prefix' => $request->getParam('prefix'),
            '_ext' => $request->getParam('_ext')
        ]);
    }
}
