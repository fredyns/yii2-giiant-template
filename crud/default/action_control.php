<?php

use yii\helpers\StringHelper;

echo "<?php\n";

$modelClass = StringHelper::basename($generator->modelClass);
$controllerNamespace = \yii\helpers\StringHelper::dirname(ltrim($generator->controllerClass, '\\'));
$actionControlNamespace = str_replace('controllers', 'actioncontrols', $controllerNamespace);
$actionControlNamespace = str_replace('Controller', 'AC', $actionControlNamespace);

$routes = [$generator->getControllerID()];

// detect additional routes
$namespaceArray = explode('\\', $actionControlNamespace);
$namespaceCount = count($namespaceArray);

if ($namespaceCount > 2) {
    $idx = $namespaceCount - 2;
    array_unshift($routes, $namespaceArray[$idx]);
}
?>

namespace <?= $actionControlNamespace ?>;

use yii\helpers\ArrayHelper;
use <?= ltrim($generator->modelClass, '\\') ?>;

/**
 * <?= $modelClass ?> model action control
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 *
 * @property <?= $modelClass ?> $model data model
 */
class <?= $modelClass ?>ActControl extends \fredyns\lbac\ActionControl
{

    /**
     * @inheritdoc
     */
    public function controllerRoute()
    {
        return '/<?= implode('/', $routes) ?>';
    }

    /**
     * @inheritdoc
     */
    public function breadcrumbLabels()
    {
        return ArrayHelper::merge(
                parent::breadcrumbLabels(), [
                'index' => '<?= $modelClass ?>',
                ]
        );
    }

}