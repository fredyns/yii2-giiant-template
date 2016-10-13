<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/*
 * @var yii\web\View $this
 * @var schmunk42\giiant\generators\crud\Generator $generator
 */

/** @var \yii\db\ActiveRecord $model */
/** @var $generator \schmunk42\giiant\generators\crud\Generator */

## TODO: move to generator (?); cleanup
$model = new $generator->modelClass();
$model->setScenario('crud');
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $model->setScenario('default');
    $safeAttributes = $model->safeAttributes();
}
if (empty($safeAttributes)) {
    $safeAttributes = $model->getTableSchema()->columnNames;
}

$modelName = Inflector::camel2words(StringHelper::basename($model::className()));
$className = $model::className();
$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use dmstr\bootstrap\Tabs;
use cornernote\returnurl\ReturnUrl;
use kartik\grid\GridView;

/**
* @var yii\web\View $this
* @var <?= ltrim($generator->modelClass, '\\') ?> $model
*/
$copyParams = $model->attributes;

$this->title = $actionControl->breadcrumbLabel('index')." "
    .$actionControl->breadcrumbLabel('view');

$this->params['breadcrumbs'][] = $actionControl->breadcrumbItem('index');
$this->params['breadcrumbs'][] = $actionControl->breadcrumbLabel('view');
?>
<div class="giiant-crud <?= Inflector::camel2id(StringHelper::basename($generator->modelClass), '-', true) ?>-view">

    <!-- flash message -->
    <?= "<?php if (\\Yii::\$app->session->getFlash('deleteError') !== null) : ?>
        <div class=\"alert alert-info alert-dismissible\" role=\"alert\">
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">
            <span aria-hidden=\"true\">&times;</span></button>
            <?= implode(\"\\n\", \Yii::\$app->session->getFlash('deleteError')); ?>
        </div>
    <?php endif; ?>" ?>


    <h1>
        <?= "<?= Yii::t('{$generator->modelMessageCategory}', '{$modelName}') ?>\n" ?>
        <small>
            <?= '<?= $model->'.$generator->getModelNameAttribute($generator->modelClass)." ?>\n" ?>
        </small>
    </h1>


    <div class="clearfix crud-navigation">

        <!-- menu buttons -->
        <div class='pull-left'>

            <?= '<?= ' ?>$actionControl->button('index'); ?>

            <?= '<?= ' ?>Html::a(
            '<span class="glyphicon glyphicon-copy"></span> ' . <?= $generator->generateString('Copy') ?>,
            ['create', 'ru' => ReturnUrl::getToken(), <?= $urlParams ?>, '<?= StringHelper::basename($generator->modelClass) ?>'=>$copyParams],
            ['class' => 'btn btn-success']) ?>

            <?= '<?= ' ?>Html::a(
            '<span class="glyphicon glyphicon-plus"></span> ' . <?= $generator->generateString('New') ?>,
            ['create', 'ru' => ReturnUrl::getToken()],
            ['class' => 'btn btn-success']) ?>
        </div>

        <div class="pull-right">
            <?= '<?= ' ?>$actionControl->dropdown(['items' => ['update', 'delete']]); ?>
        </div>

    </div>

    <hr />

    <?php
    echo "<?php \$this->beginBlock('{$generator->modelClass}'); ?>\n";
    ?>

    <?= $generator->partialView('detail_prepend', $model); ?>

    <?= '<?= ' ?>DetailView::widget([
    'model' => $model,
    'attributes' => [
    <?php

    $hidenAttributes = [
        'id',
        'recordStatus',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    $safeAttributes = array_diff($safeAttributes, $hidenAttributes);

    foreach ($safeAttributes as $attribute) {
        $format = $generator->attributeFormat($attribute);
        if (!$format) {
            continue;
        } else {
            echo $format.",\n";
        }
    }
    ?>
    ],
    ]); ?>

    <?= $generator->partialView('detail_append', $model); ?>

    <?= "<?php \$this->endBlock(); ?>\n\n"; ?>

    <?php

    // get relation info $ prepare add button
    $model = new $generator->modelClass();

    $items = <<<EOS
[
    'label'   => '<b class=""># '.\$model->{$model->primaryKey()[0]}.'</b>',
    'content' => \$this->blocks['{$generator->modelClass}'],
    'active'  => true,
],

EOS;

        // formulate action controls
        $actControlNamespace = StringHelper::dirname(ltrim($generator->controllerClass, '\\'));
        $actControlNamespace = str_replace('controllers', 'actioncontrols', $actControlNamespace);

    foreach ($generator->getModelRelations($generator->modelClass, ['has_many']) as $name => $relation) {
        echo "\n<?php \$this->beginBlock('$name'); ?>\n";

        $showAllRecords = false;

        // render pivot grid
        if ($relation->via !== null) {
            $pjaxId = "pjax-{$pivotName}";
            $gridRelation = $pivotRelation;
            $gridName = $pivotName;
        } else {
            $pjaxId = "pjax-{$name}";
            $gridRelation = $relation;
            $gridName = $name;
        }

        $output = $generator->relationGrid($gridName, $gridRelation, $showAllRecords);

        // render relation grid
        if (!empty($output)):
            echo "<?php Pjax::begin(['id'=>'pjax-{$name}', 'enableReplaceState'=> false, 'linkSelector'=>'#pjax-{$name} ul.pagination a, th a', 'clientOptions' => ['pjax:success'=>'function(){alert(\"yo\")}']]) ?>\n";
            echo "<?php\n ".$output."\n?>\n";
            echo "<?php Pjax::end() ?>\n";
        endif;

        echo "<?php \$this->endBlock() ?>\n\n";

        // build tab items
        $label = Inflector::camel2words($name);
        $items .= <<<EOS
[
    'content' => \$this->blocks['$name'],
    'label'   => '<small>$label <span class="badge badge-default">'.count(\$model->get{$name}()->asArray()->all()).'</span></small>',
    'active'  => false,
],\n
EOS;
    }
    ?>

    <?=
    // render tabs
    "<?= Tabs::widget(
                 [
                     'id' => 'relation-tabs',
                     'encodeLabels' => false,
                     'items' => [\n $items ]
                 ]
    );
    ?>";
    ?>

</div>
