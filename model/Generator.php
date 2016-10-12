<?php
/**
 * @link http://www.phundament.com
 *
 * @copyright Copyright (c) 2014 herzog kommunikation GmbH
 * @license http://www.phundament.com/license/
 */

namespace fredyns\giiantTemplate\model;

use Yii;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use schmunk42\giiant\helpers\SaveForm;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Fredy Nurman Saleh <email@fredyns.net>
 */
class Generator extends \schmunk42\giiant\generators\model\Generator
{

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        return ['model.php', 'model-extended.php', 'model-form.php', 'action_control.php'];
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $files     = [];
        $relations = $this->generateRelations();
        $db        = $this->getDbConnection();

        foreach ($this->getTableNames() as $tableName)
        {
            list($relations, $translations) = array_values($this->extractTranslations($tableName, $relations));

            $className      = $this->generateClassName($tableName);
            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($className) : false;
            $tableSchema    = $db->getTableSchema($tableName);

            $params = [
                'tableName'      => $tableName,
                'className'      => $className,
                'queryClassName' => $queryClassName,
                'tableSchema'    => $tableSchema,
                'labels'         => $this->generateLabels($tableSchema),
                'hints'          => $this->generateHints($tableSchema),
                'rules'          => $this->generateRules($tableSchema),
                'relations'      => isset($relations[$tableName]) ? $relations[$tableName] : [],
                'ns'             => $this->ns,
                'enum'           => $this->getEnum($tableSchema->columns),
            ];

            if (!empty($translations))
            {
                $params['translation'] = $translations;
            }

            $params['blameable'] = $this->generateBlameable($tableSchema);
            $params['timestamp'] = $this->generateTimestamp($tableSchema);

            $files[] = new CodeFile(
                Yii::getAlias(
                    '@'.str_replace('\\', '/', $this->ns)
                ).'/base/'.$className.$this->baseClassSuffix.'.php', $this->render('model.php', $params)
            );

            $modelClassFile = Yii::getAlias('@'.str_replace('\\', '/', $this->ns)).'/'.$className.'.php';

            if ($this->generateModelClass || !is_file($modelClassFile))
            {
                $files[] = new CodeFile(
                    $modelClassFile, $this->render('model-extended.php', $params)
                );

                /**
                 * Fredy:start=====================
                 *
                 * add form model & action_control
                 */
                $formClassFile = Yii::getAlias('@'.str_replace('\\', '/', $this->ns)).'/form/'.$className.'Form.php';

                $files[] = new CodeFile(
                    $formClassFile, $this->render('model-form.php', $params)
                );

                $actionControlClassFile = Yii::getAlias('@'.str_replace('\\', '/', $this->ns)).'/'.$className.'ActControl.php';
                $actionControlClassFile = str_replace('models', 'actioncontrols', $actionControlClassFile);

                $files[] = new CodeFile(
                    $actionControlClassFile, $this->render('action_control.php', $params)
                );
                /**
                 * Fredy:end==================
                 */
            }

            if ($queryClassName)
            {
                $queryClassFile = Yii::getAlias(
                        '@'.str_replace('\\', '/', $this->queryNs)
                    ).'/'.$queryClassName.'.php';
                if ($this->generateModelClass || !is_file($queryClassFile))
                {
                    $params  = [
                        'className'      => $queryClassName,
                        'modelClassName' => $className,
                    ];
                    $files[] = new CodeFile(
                        $queryClassFile, $this->render('query.php', $params)
                    );
                }
            }

            /*
             * create gii/[name]GiiantModel.json with actual form data
             */
            $suffix       = str_replace(' ', '', $this->getName());
            $formDataDir  = Yii::getAlias('@'.str_replace('\\', '/', $this->ns));
            $formDataFile = StringHelper::dirname($formDataDir)
                .'/gii'
                .'/'.$tableName.$suffix.'.json';

            $formData = json_encode(SaveForm::getFormAttributesValues($this, $this->formAttributes()));
            $files[]  = new CodeFile($formDataFile, $formData);
        }

        return $files;
    }


}