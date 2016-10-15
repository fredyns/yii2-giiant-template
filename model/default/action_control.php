<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * This is the template for generating the model class of a specified table.
 *
 * @var yii\web\View $this
 * @var yii\gii\generators\model\Generator $generator
 * @var string $tableName full table name
 * @var string $className class name
 * @var yii\db\TableSchema $tableSchema
 * @var string[] $labels list of attribute labels (name => label)
 * @var string[] $rules list of validation rules
 * @var array $relations list of relations (name => relation declaration)
 */

echo "<?php\n";

$actionControlNamespace = str_replace('models', 'actioncontrols', $generator->ns);
$routes = [Inflector::camel2id($className, '-', true)]; 

// detect additional routes
$namespaceArray = explode('\\', $generator->ns);
$namespaceCount = count($namespaceArray);

if ($namespaceCount > 2) {
    $idx = $namespaceCount - 2;
    array_unshift($routes, $namespaceArray[$idx]);
}
?>

namespace <?= $actionControlNamespace ?>;

use yii\helpers\ArrayHelper;
use <?= $generator->ns . '\\' . $className ?>;

/**
 * <?= $className ?> model action control
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 *
 * @property <?= $className ?> $model data model
 */
class <?= $className ?>ActControl extends \fredyns\lbac\ActionControl
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
                'index' => '<?= $className ?>',
                ]
        );
    }

<?php if ($tableSchema->getColumn('deleted_at') !== null): ?>
    /**
     * check permission to access Deleted page
     *
     * @return boolean
     */
    public function getAllowDeleted()
    {
        return true;
    }
<?php endif; ?>

}