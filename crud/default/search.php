<?php
/**
 * This is the template for generating CRUD search class of the specified model.
 */
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $modelAlias = $modelClass.'Model';
}
$rules = $generator->generateSearchRules();
$labels = $generator->generateSearchLabels();
$searchAttributes = $generator->getSearchAttributes();
$searchConditions = $generator->generateSearchConditions();

$softDelete = in_array('fredyns\components\traits\ModelSoftDelete', class_uses($generator->modelClass));

echo "<?php\n";
?>

namespace <?= StringHelper::dirname(ltrim($generator->searchModelClass, '\\')) ?>;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use <?= ltrim($generator->modelClass, '\\').(isset($modelAlias) ? " as $modelAlias" : '') ?>;

/**
* <?= $searchModelClass ?> represents the model behind the search form about `<?= $generator->modelClass ?>`.
*/
class <?= $searchModelClass ?> extends <?= isset($modelAlias) ? $modelAlias : $modelClass ?>

{
/**
* @inheritdoc
*/
public function rules()
{
return [
<?= implode(",\n            ", $rules) ?>,
];
}

/**
* @inheritdoc
*/
public function scenarios()
{
// bypass scenarios() implementation in the parent class
return Model::scenarios();
}

    /**
     * search models
     *
     * @param array   $params
     * 
     * @return ActiveDataProvider
     */
    public function index($params)
    {
        $this->load($params);

<?php if ($softDelete): ?>

        $this->recordStatus = static::RECORDSTATUS_ACTIVE;

<?php endif; ?>

        return $this->search();
    }

<?php if ($softDelete): ?>

    /**
     * search deleted models
     *
     * @param array   $params
     *
     * @return ActiveDataProvider
     */
    public function deleted($params)
    {
        $this->load($params);

        $this->recordStatus = static::RECORDSTATUS_DELETED;

        return $this->search();
    }

<?php endif; ?>

/**
* Creates data provider instance with search query applied
*
* @param array $params
*
* @return ActiveDataProvider
*/
public function search()
{
$query = <?= isset($modelAlias) ? $modelAlias : $modelClass ?>::find();

$dataProvider = new ActiveDataProvider([
        'query' => $query,
        'pagination' => [
            'pageSize' => 50,
        ],
]);

if (!$this->validate()) {
// uncomment the following line if you do not want to any records when validation fails
// $query->where('0=1');
return $dataProvider;
}

<?= implode("\n        ", $searchConditions) ?>

return $dataProvider;
}
}