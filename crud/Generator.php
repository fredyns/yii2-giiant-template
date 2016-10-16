<?php
/**
 * @link      http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace fredyns\giiantTemplate\crud;

use Yii;
use yii\gii\CodeFile;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use schmunk42\giiant\helpers\SaveForm;

/**
 * This generator generates an extended version of CRUDs.
 *
 * @author Tobais Munk <schmunk@usrbin.de>
 *
 * @since 1.0
 */
class Generator extends \schmunk42\giiant\generators\crud\Generator
{
    public $actionButtonClass = 'fredyns\\lbac\\KartikActionColumn';
    public $indexGridClass    = 'kartik\\grid\\GridView';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->providerList = static::getProviders();
    }

    /**
     * @return array List of providers. Keys and values contain the same strings
     */
    public function generateProviderCheckboxListData()
    {
        $coreProviders = static::getProviders();

        return array_combine($coreProviders, $coreProviders);
    }

    public static function getProviders()
    {
        $files = FileHelper::findFiles(
                Yii::getAlias('@vendor/fredyns/yii2-giiant-template/crud/providers'), [
                'only'      => ['*.php'],
                'recursive' => TRUE,
                ]
        );

        foreach ($files as $file)
        {
            require_once $file;
        }

        return array_filter(
            get_declared_classes(), function ($a)
        {
            return stripos($a, __NAMESPACE__.'\providers') !== false;
        }
        );
    }

    public function render($template, $params = [])
    {
        $code   = parent::render($template, $params);
        $tmpDir = Yii::getAlias('@runtime/giiant');

        FileHelper::createDirectory($tmpDir);

        $tmpFile = $tmpDir.'/'.md5($template);

        file_put_contents($tmpFile, $code);

        $command = Yii::getAlias('@vendor/bin/phptidy').' replace '.$tmpFile;

        shell_exec($command);

        return file_get_contents($tmpFile);
    }

    public function generate()
    {
        $accessDefinitions = require $this->getTemplatePath().'/access_definition.php';

        $this->controllerNs = \yii\helpers\StringHelper::dirname(ltrim($this->controllerClass, '\\'));
        $this->moduleNs     = \yii\helpers\StringHelper::dirname(ltrim($this->controllerNs, '\\'));
        $controllerName     = substr(\yii\helpers\StringHelper::basename($this->controllerClass), 0, -10);

        if ($this->singularEntities)
        {
            $this->modelClass       = Inflector::singularize($this->modelClass);
            $this->controllerClass  = Inflector::singularize(
                    substr($this->controllerClass, 0, strlen($this->controllerClass) - 10)
                ).'Controller';
            $this->searchModelClass = Inflector::singularize($this->searchModelClass);
        }

        $controllerFile     = Yii::getAlias('@'.str_replace('\\', '/', ltrim($this->controllerClass, '\\')).'.php');
        $baseControllerFile = StringHelper::dirname($controllerFile).'/base/'.StringHelper::basename($controllerFile);
        $restControllerFile = StringHelper::dirname($controllerFile).'/api/'.StringHelper::basename($controllerFile);

        /**
         * fredy:start ========================================
         */
        $actionControlFile = str_replace('controllers', 'actioncontrols', $controllerFile);
        $actionControlFile = str_replace('Controller', 'ActControl', $actionControlFile);
        /**
         * fredy:end ========================================
         */
        /*
         * search generated migration and overwrite it or create new
         */
        $migrationDir      = StringHelper::dirname(StringHelper::dirname($controllerFile))
            .'/migrations';

        if (file_exists($migrationDir) && $migrationDirFiles = glob($migrationDir.'/m*_'.$controllerName.'00_access.php'))
        {
            $this->migrationClass = pathinfo($migrationDirFiles[0], PATHINFO_FILENAME);
        }
        else
        {
            $this->migrationClass = 'm'.date('ymd_Hi').'00_'.$controllerName.'_access';
        }

        $files[]                       = new CodeFile($baseControllerFile, $this->render('controller.php', ['accessDefinitions' => $accessDefinitions]));
        $params['controllerClassName'] = \yii\helpers\StringHelper::basename($this->controllerClass);

        /**
         * fredy:start ========================================
         */
        $files[] = new CodeFile($actionControlFile, $this->render('action_control.php', $params));
        /**
         * fredy:end ========================================
         */
        if ($this->overwriteControllerClass || !is_file($controllerFile))
        {
            $files[] = new CodeFile($controllerFile, $this->render('controller-extended.php', $params));
        }

        if ($this->overwriteRestControllerClass || !is_file($restControllerFile))
        {
            $files[] = new CodeFile($restControllerFile, $this->render('controller-rest.php', $params));
        }

        if (!empty($this->searchModelClass))
        {
            $searchModel = Yii::getAlias('@'.str_replace('\\', '/', ltrim($this->searchModelClass, '\\').'.php'));

            $files[] = new CodeFile($searchModel, $this->render('search.php'));
        }

        $viewPath     = $this->getViewPath();
        $templatePath = $this->getTemplatePath().'/views';

        foreach (scandir($templatePath) as $file)
        {
            if (empty($this->searchModelClass) && $file === '_search.php')
            {
                continue;
            }
            if (is_file($templatePath.'/'.$file) && pathinfo($file, PATHINFO_EXTENSION) === 'php')
            {
                echo $file;
                $files[] = new CodeFile("$viewPath/$file", $this->render("views/$file", ['permisions' => $permisions]));
            }
        }

        if ($this->generateAccessFilterMigrations)
        {

            /*
             * access migration
             */
            $migrationFile = $migrationDir.'/'.$this->migrationClass.'.php';
            //var_dump($migrationFile);exit;
            $files[]       = new CodeFile($migrationFile, $this->render('migration_access.php', ['accessDefinitions' => $accessDefinitions]));

            /*
             * access roles translation
             */
            $forRoleTranslationFile = StringHelper::dirname(StringHelper::dirname($controllerFile))
                .'/messages/for-translation/'
                .$controllerName.'.php';
            $files[]                = new CodeFile($forRoleTranslationFile, $this->render('roles-translation.php', ['accessDefinitions' => $accessDefinitions]));
        }

        /*
         * create gii/[name]GiantCRUD.json with actual form data
         */
        $suffix             = str_replace(' ', '', $this->getName());
        $controllerFileinfo = pathinfo($controllerFile);
        $formDataFile       = StringHelper::dirname(StringHelper::dirname($controllerFile))
            .'/gii/'
            .str_replace('Controller', $suffix, $controllerFileinfo['filename']).'.json';
        //$formData = json_encode($this->getFormAttributesValues());
        $formData           = json_encode(SaveForm::getFormAttributesValues($this, $this->formAttributes()));
        $files[]            = new CodeFile($formDataFile, $formData);

        return $files;
    }

    /**
     * search suficient model label
     *
     * @return string
     */
    public function getModelLabel()
    {
        $model        = new $this->modelClass;
        $alternatives = [
            'name',
            'title',
            'label',
            'number',
            'user_id',
            'localtime',
        ];

        foreach ($alternatives as $attribute)
        {
            if ($model->hasAttribute($attribute))
            {
                return $attribute;
            }
        }

        return $model->primaryKey()[0];
    }

}