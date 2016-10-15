<?php
/**
 * Created by PhpStorm.
 * User: tobias
 * Date: 14.03.14
 * Time: 10:21.
 */

namespace fredyns\giiantTemplate\crud\providers\core;

use fredyns\giiantTemplate\model\Generator as ModelGenerator;
use yii\db\ActiveRecord;
use yii\db\ColumnSchema;
use yii\helpers\Inflector;

class RelationProvider extends \schmunk42\giiant\generators\crud\providers\core\RelationProvider
{

    /**
     * Formatter for relation grid columns.
     *
     * Renders a link to the related detail view
     *
     * @param $column ColumnSchema
     * @param $model ActiveRecord
     *
     * @return null|string
     */
    public function columnFormat($attribute, $model)
    {
        $column = $this->generator->getColumnByAttribute($attribute, $model);
        if (!$column)
        {
            return;
        }

        // handle columns with a primary key, to create links in pivot tables (changed at 0.3-dev; 03.02.2015)
        // TODO double check with primary keys not named `id` of non-pivot tables
        // TODO Note: condition does not apply in every case
        if ($column->isPrimaryKey)
        {
            //return null;
        }

        $relation = $this->generator->getRelationByColumn($model, $column);
        if ($relation)
        {
            if ($relation->multiple)
            {
                return;
            }
            $title           = $this->generator->getModelNameAttribute($relation->modelClass);
            $route           = $this->generator->createRelationRoute($relation, 'view');
            $method          = __METHOD__;
            $modelClass      = $this->generator->modelClass;
            $relationGetter  = 'get'.(new ModelGenerator())->generateRelationName(
                    [$relation], $modelClass::getTableSchema(), $column->name, $relation->multiple
                ).'()';
            $relationModel   = new $relation->modelClass();
            $pks             = $relationModel->primaryKey();
            $paramArrayItems = '';

            foreach ($pks as $attr)
            {
                $paramArrayItems .= "'{$attr}' => \$rel->{$attr},";
            }

            $code = <<<EOS
[
    'attribute' => '{$column->name}',
    'options' => [],
    'format' => 'raw',
    'value' => function (\$model) {
        if (\$rel = \$model->{$relationGetter}->one()) {
            return Html::a(\$rel->{$title}, ['{$route}', 'ru' => ReturnUrl::getToken(), {$paramArrayItems}], ['data-pjax' => 0]);
        } else {
            return '';
        }
    },
]
EOS;

            return $code;
        }
    }

    /**
     * Renders a grid view for a given relation.
     *
     * @param $name
     * @param $relation
     * @param bool $showAllRecords
     *
     * @return mixed|string
     */
    public function relationGrid($name, $relation, $showAllRecords = false)
    {
        $model = new $relation->modelClass();

// column counter
        $counter = 0;
        $columns = <<<EOS
[
                    'class' => 'kartik\grid\SerialColumn',
],
EOS;

        if (!$this->generator->isPivotRelation($relation))
        {
            // hasMany relations
            $template          = '{view} {update}';
            $deleteButtonPivot = '';
        }
        else
        {
            // manyMany relations
            $template          = '{view} {delete}';
            $deleteButtonPivot = <<<EOS
'delete' => function (\$url, \$model) {
                return Html::a('<span class="glyphicon glyphicon-remove"></span>', \$url, [
                    'class' => 'text-danger',
                    'title'         => {$this->generator->generateString('Remove')},
                    'data-confirm'  => {$this->generator->generateString(
                    'Are you sure you want to delete the related item?'
                )},
                    'data-method' => 'post',
                    'data-pjax' => '0',
                ]);
            },
'view' => function (\$url, \$model) {
                return Html::a(
                    '<span class="glyphicon glyphicon-cog"></span>',
                    \$url,
                    [
                        'data-title'  => {$this->generator->generateString('View Pivot Record')},
                        'data-toggle' => 'tooltip',
                        'data-pjax'   => '0',
                        'class'       => 'text-muted',
                    ]
                );
            },
EOS;
        }

        $reflection             = new \ReflectionClass($relation->modelClass);
        $actionControlNamespace = strstr($this->generator->modelClass, 'models\\', TRUE).'actioncontrols';
        $modelClass             = $reflection->getShortName();
        $actionControlClass     = $modelClass.'ActControl';
        $actionControlClassname = $actionControlNamespace.'\\'.$actionControlClass;

        $actionColumn = <<<EOS
          [
          'class' => 'fredyns\lbac\KartikActionColumn',
          'actionControl' => '{$actionControlClassname}',
          ],
EOS;

        // prepare grid column formatters
        $model->setScenario('crud');
        $safeAttributes = $model->safeAttributes();
        if (empty($safeAttributes))
        {
            $safeAttributes = $model->getTableSchema()->columnNames;
        }

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

        foreach ($safeAttributes as $attr)
        {

            // max seven columns
            if ($counter > $this->generator->gridRelationMaxColumns)
            {
                continue;
            }
            // skip virtual attributes
            if ($this->skipVirtualAttributes && !isset($model->tableSchema->columns[$attr]))
            {
                continue;
            }
            // don't show current model
            if (key($relation->link) == $attr)
            {
                continue;
            }

            $code = $this->generator->columnFormat($attr, $model);
            if ($code == false)
            {
                continue;
            }
            $columns .= $code.",\n";
            ++$counter;
        }

        // add action column

            $columns .= $actionColumn."\n";

        $query          = $showAllRecords ?
            "'query' => \\{$relation->modelClass}::find()" :
            "'query' => \$model->get{$name}()";
        $pageParam      = Inflector::slug("page-{$name}");
        $firstPageLabel = $this->generator->generateString('First');
        $lastPageLabel  = $this->generator->generateString('Last');
        $relationship   = "'".key($relation->link)."' => \$model->".(new $this->generator->modelClass)->primaryKey()[0];
        $code           = <<<EOS

\${$actionControlClass} = new \\{$actionControlClassname};

\$add{$modelClass} = \${$actionControlClass}->button('create', [
    'label'      => 'New {$modelClass}',
    'urlOptions' => [
        '{$modelClass}Form' => [{$relationship}],
    ],
]);

echo '<div class=\"table-responsive\">';
echo GridView::widget([
    //'layout' => '{summary}{pager}<br/>{items}{pager}',
    'dataProvider' => new \\yii\\data\\ActiveDataProvider([
        {$query},
        'pagination' => [
            'pageSize' => 50,
            'pageParam'=>'{$pageParam}',
        ]
    ]),
    'pager'        => [
        'class'          => yii\widgets\LinkPager::className(),
        'firstPageLabel' => {$firstPageLabel},
        'lastPageLabel'  => {$lastPageLabel}
    ],
	'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
	'headerRowOptions' => ['class'=>'x'],
    'columns' => [\n $columns],
	'containerOptions'    => ['style' => 'overflow: auto'], // only set when \$responsive = false
	'headerRowOptions'    => ['class' => 'kartik-sheet-style'],
	'filterRowOptions'    => ['class' => 'kartik-sheet-style'],
	'pjax'                => FALSE, // pjax is set to always true for this demo
	'toolbar'             => [
		\$add{$modelClass}.' {export}',
	],
	'export'              => [
		'icon'  => 'export',
		'label' => 'Export',
	],
	//'bordered'            => true,
	'striped'             => true,
	'condensed'           => true,
	'responsive'          => true,
	'hover'               => true,
	'showPageSummary'     => true,
            'pageSummaryRowOptions' => [
                'class' => 'kv-page-summary',
                'style' => 'height: 100px;'
            ],
	'persistResize'       => false,
	'exportConfig'        => [
		GridView::EXCEL => [
			'label'    => 'Save as EXCEL',
			'filename' => \$this->title . ' - {$modelClass}',
		],
		GridView::PDF   => [
			'label'    => 'Save as PDF',
			'filename' => \$this->title . ' - {$modelClass}',
		],
	],
	'panel'               => [
		'type' => GridView::TYPE_PRIMARY,
        'heading' => false,
	],
	'panelBeforeTemplate' => '
                <div class="clearfix">{summary}</div>
                <div class="pull-right">
                    <div class="btn-toolbar kv-grid-toolbar" role="toolbar">
                        {toolbar}
                    </div>
                </div>
                <div class="pull-left">
                    <div class="kv-panel-pager">
                        {pager}
                    </div>
                </div>
                {before}
                <div class="clearfix"></div>
    ',
]);
echo '</div>';
EOS;
        return $code;
    }

}