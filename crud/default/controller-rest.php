<?php
/**
 * Customizable controller class.
 */
echo "<?php\n";

$actControlNamespace = str_replace('controllers', 'actioncontrols', $generator->controllerNs);
$actControlClass = str_replace('Controller', 'ActControl', $controllerClassName);
?>

namespace <?= $generator->controllerNs ?>\api;

/**
* This is the class for REST controller "<?= $controllerClassName ?>".
* empowered with logic base access control
*/

use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use <?= $actControlNamespace.'\\'.$actControlClass ?>;

class <?= $controllerClassName ?> extends \yii\rest\ActiveController
{
public $modelClass = '<?= $generator->modelClass ?>';
<?php if ($generator->accessFilter): ?>
    /**
    * @inheritdoc
    */
    public function behaviors()
    {
    return ArrayHelper::merge(
    parent::behaviors(),
    [
    'access' => [
    'class' => AccessControl::className(),
    'rules' => [
    [
    'allow' => true,
    'matchCallback' => function ($rule, $action) {return \Yii::$app->user->can($this->module->id . '_' . $this->id . '_' . $action->id, ['route' => true]);},
    ]
    ]
    ]
    ]
    );
    }
<?php endif; ?>

    /**
     * @inheritdoc
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $actControl = new <?= $actControlClass ?>(['model' => $model]);

        return $actControl->allow($action, true);
    }

}
