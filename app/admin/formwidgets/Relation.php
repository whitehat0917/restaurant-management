<?php namespace Admin\FormWidgets;

use Exception;
use Admin\Classes\FormField;
use Admin\Classes\BaseFormWidget;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation as RelationBase;

/**
 * Form Relationship
 * Renders a field prepopulated with a belongsTo and belongsToHasMany relation.
 *
 * Adapted from october\backend\formwidgets\Relation
 *
 * @package Admin
 */
class Relation extends BaseFormWidget
{

    //
    // Configurable properties
    //

    /**
     * @var string Relation name, if this field name does not represents a model relationship.
     */
    public $relationFrom;

    /**
     * @var string Model column to use for the name reference
     */
    public $nameFrom = 'name';

    /**
     * @var string Model column to use for the description reference
     */
    public $descriptionFrom = 'description';

    /**
     * @var string Custom SQL column selection to use for the name reference
     */
    public $sqlSelect;

    /**
     * @var string Empty value to use if the relation is singluar (belongsTo)
     */
    public $emptyOption;

    public $relatedModel;

    //
    // Object properties
    //

    protected $defaultAlias = 'relation';

    /**
     * @var FormField Object used for rendering a simple field type
     */
    public $clonedFormField;

    public function initialize()
    {
        $this->fillFromConfig([
            'relationFrom',
            'nameFrom',
            'descriptionFrom',
            'emptyOption',
        ]);

        if (isset($this->config['select'])) {
            $this->sqlSelect = $this->config['select'];
        }
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('relation/relation');
    }

    public function getSaveValue($value)
    {
        if ($this->formField->disabled || $this->formField->hidden) {
            return FormField::NO_SAVE_DATA;
        }

        if (is_string($value) && !strlen($value)) {
            return null;
        }

        if (is_array($value) && !count($value)) {
            return null;
        }

        return $value;
    }

    public function prepareVars()
    {
        $this->vars['field'] = $this->makeFormField();
    }

    /**
     * Returns the final model and attribute name of
     * a nested HTML array attribute.
     * Eg: list($model, $attribute) = $this->resolveModelAttribute($this->valueFrom);
     *
     * @param  string $attribute .
     *
     * @return array
     */
    public function resolveModelAttribute($attribute)
    {
        $attribute = $this->relationFrom ? $this->relationFrom : $attribute;

        return $this->formField->resolveModelAttribute($this->model, $attribute);
    }

    /**
     * Makes the form object used for rendering a simple field type
     */
    protected function makeFormField()
    {
        return $this->clonedFormField = RelationBase::noConstraints(function () {

            $field = clone $this->formField;
            $relationObject = $this->getRelationObject();
            $query = $relationObject->newQuery();

            list($model, $attribute) = $this->resolveModelAttribute($this->valueFrom);
            $relationType = $model->getRelationType($attribute);
            $this->relatedModel = $model->makeRelation($attribute);

            $field->type = 'selectlist';
            if (in_array($relationType, ['belongsToMany', 'morphToMany', 'morphedByMany', 'hasMany'])) {
                $field->mode = 'checkbox';
            }
            elseif (in_array($relationType, ['belongsTo', 'hasOne'])) {
                $field->mode = 'radio';
            }

            $field->value = $this->processFieldValue($this->relatedModel, $this->getLoadValue());
            $field->placeholder = $field->placeholder ? $field->placeholder : $this->emptyOption;

            // It is safe to assume that if the model and related model are of
            // the exact same class, then it cannot be related to itself
            if ($model->exists && (get_class($model) == get_class($this->relatedModel))) {
                $query->where($this->relatedModel->getKeyName(), '<>', $model->getKey());
            }

            // Even though "no constraints" is applied, belongsToMany constrains the query
            // by joining its pivot table. Remove all joins from the query.
            $query->getQuery()->getQuery()->joins = [];

            // The "sqlSelect" config takes precedence over "nameFrom".
            // A virtual column called "selection" will contain the result.
            // Tree models must select all columns to return parent columns, etc.
            if ($this->sqlSelect) {
                $nameFrom = 'selection';
                $selectColumn = $this->relatedModel->getKeyName();
                $result = $query->select($selectColumn, DB::raw($this->sqlSelect.' AS '.$nameFrom));
            }
            else {
                $nameFrom = $this->nameFrom;
                $result = $query->getQuery()->get();
            }

            $field->options = $result->pluck($nameFrom, $this->relatedModel->getKeyName())->all();

            return $field;
        });
    }

    protected function processFieldValue($model, $value)
    {
        if ($value instanceof Collection)
            $value = $value->pluck($model->getKeyName())->toArray();

        return $value;
    }

    /**
     * Returns the value as a relation object from the model,
     * supports nesting via HTML array.
     * @return \Admin\FormWidgets\Relation
     * @throws \Exception
     */
    protected function getRelationObject()
    {
        list($model, $attribute) = $this->resolveModelAttribute($this->valueFrom);

        if (!$model OR !$model->hasRelation($attribute)) {
            throw new Exception(sprintf("Model '%s' does not contain a definition for '%s'.",
                get_class($this->model),
                $this->valueFrom
            ));
        }

        return $model->{$attribute}();
    }
}
