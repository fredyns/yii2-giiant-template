<?php
/**
 * Customizable controller class.
 */
echo "<?php\n";
?>

namespace <?= \yii\helpers\StringHelper::dirname(ltrim($generator->controllerClass, '\\')) ?>;

use app\filters\Layout;

/**
* This is the class for controller "<?= $controllerClassName ?>".
*/
class <?= $controllerClassName ?> extends <?= (isset($generator->controllerNs) ? '\\'.$generator->controllerNs.'\\' : '') .'base\\'.$controllerClassName."\n" ?>
{
    public function behaviors()
    {
        return [
            'layout' => [
                'class' => Layout::className(),
            ],
        ];
    }


}
