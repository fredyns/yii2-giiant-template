<?php
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

$modelTrait = ['ModelTool', 'ModelBlame'];

if ($tableSchema->getColumn('deleted_at') !== null) {
    $modelTrait[] = 'ModelSoftDelete';
}
?>

namespace <?= $generator->ns ?>;

use Yii;
use yii\helpers\ArrayHelper;
use <?= $generator->ns ?>\base\<?= $className ?> as Base<?= $className ?>;
use fredyns\components\traits\ModelTool;
use fredyns\components\traits\ModelBlame;
use fredyns\components\traits\ModelSoftDelete;

/**
 * This is the model class for table "<?= $tableName ?>".
 */
class <?= $className ?> extends Base<?= $className . "\n" ?>
{

use <?= implode(', ', $modelTrait) ?>;

public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                # custom behaviors
            ]
        );
    }

    public function rules()
    {
        return ArrayHelper::merge(
             parent::rules(),
             [
                  # custom validation rules
             ]
        );
    }
}
