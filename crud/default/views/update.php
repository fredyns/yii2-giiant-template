<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/*
 * @var yii\web\View $this
 * @var yii\gii\generators\crud\Generator $generator
 */

$urlParams = $generator->generateUrlParams();
$model = new $generator->modelClass();
$model->setScenario('crud');
$className = $model::className();
$modelName = Inflector::camel2words(StringHelper::basename($model::className()));

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\helpers\Url;
use cornernote\returnurl\ReturnUrl;

/**
* @var yii\web\View $this
* @var <?= ltrim($generator->modelClass, '\\') ?> $model
*/
    
$this->title = $actionControl->breadcrumbLabel('index')." "
    .$actionControl->breadcrumbLabel('view').', '
    .$actionControl->breadcrumbLabel('update');

$this->params['breadcrumbs'][] = $actionControl->breadcrumbItem('index');
$this->params['breadcrumbs'][] = $actionControl->breadcrumbItem('view');
$this->params['breadcrumbs'][] = $actionControl->breadcrumbLabel('update');
?>
<div class="giiant-crud <?= Inflector::camel2id(StringHelper::basename($generator->modelClass), '-', true) ?>-update">

    <h1>
        <?= "<?= Yii::t('{$generator->modelMessageCategory}', '{$modelName}') ?>" ?>

        <small>
            <?php $label = StringHelper::basename($generator->modelClass); ?>
            <?= '<?= $model->'.$generator->getModelNameAttribute($generator->modelClass)." ?>\n" ?>
        </small>
    </h1>

    <div class="crud-navigation">
        <?= '<?= ' ?>Html::a('<span class="glyphicon glyphicon-remove"></span> ' . <?= $generator->generateString('Cancel') ?>, ReturnUrl::getUrl(Url::previous()), ['class' => 'btn btn-default']) ?>
        <?= '<?= ' ?>$actionControl->button('view'); ?>
    </div>

    <hr />

    <?= '<?php ' ?>echo $this->render('_form', [
    'model' => $model,
    ]); ?>

</div>
