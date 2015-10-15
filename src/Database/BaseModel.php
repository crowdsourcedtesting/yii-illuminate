<?php
namespace CST\Yii\Illuminate\Database;

use Carbon\Carbon;
use Illuminate\Support\Str;

abstract class BaseModel extends \CActiveRecord
{
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = [];

    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The cache of the mutated attributes for each class.
     *
     * @var array
     */
    protected static $mutatorCache = [];

    /**
     * Save a new model and return the instance.
     * @param $attributes
     * @return static
     */
    public static function create($attributes)
    {
        $model = new static();
        $model->setAttributes($attributes);
        $model->save();

        return $model;
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     * @param $attributes
     * @return static
     */
    public static function firstOrNew($attributes)
    {
        //try to load model with available id i.e. unique key
        $model = static::model()->findByAttributes($attributes);

        if (!$model) {
            $model = new static();
            $model->setAttributes($attributes);
        }

        return $model;
    }

    /**
     * Get the first record matching the attributes or create it.
     * @param $attributes
     * @return static
     */
    public static function firstOrCreate($attributes)
    {
        $model = static::model()->firstOrNew($attributes);
        if ($model->getIsNewRecord()) {
            $model->save();
        }

        return $model;
    }

    /**
     * Find a model by its primary key or throw an exception.
     * @param $id
     * @param array $columns
     * @return static
     * @throws \CDbException
     */
    public static function findOrFail($id, array $columns = [])
    {
        $pk = static::model()->tableSchema->primaryKey;
        $conditions = [];

        if (!empty($columns)) {
            $conditions['select'] = $columns;
        }

        $model = static::model()->findByAttributes([$pk => $id], $conditions);

        if (!$model) {
            throw new \CDbException('Model not found');
        }

        return $model;
    }

    /**
     * Create a new model instance and set the given attributes
     * @param array $attributes
     * @param null $scenario
     * @return static
     */
    public static function newWithInput(array $attributes, $scenario = null)
    {
        $model = new static();
        $model->setScenario($scenario);
        $model->setAttributes($attributes);
        return $model;
    }

    /**
     * Sets the scenario for the model.
     * @param string $value the scenario that this model is in.
     * @see getScenario
     * @return $this
     */
    public function setScenario($value)
    {
        parent::setScenario($value);

        return $this;
    }

    protected function beforeValidate()
    {
        if ($this->timestamps) {
            $this->updateTimestamps();
        }

        return parent::beforeValidate();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTime  $date
     * @return string
     */
    protected function serializeDate(\DateTime $date)
    {
        return $date->format($this->getDateFormat());
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTime) {
            //
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        elseif (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        elseif (!$value instanceof DateTime) {
            $format = $this->getDateFormat();

            return Carbon::createFromFormat($format, $value);
        }

        return Carbon::instance($value);
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param  \DateTime|int $value
     * @return string
     */
    protected function fromDateTime($value)
    {
        $format = $this->getDateFormat();

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTime) {
            //
        }

        // If the value is totally numeric, we will assume it is a UNIX timestamp and
        // format the date as such. Once we have the date in DateTime form we will
        // format it according to the proper format for the database connection.
        elseif (is_numeric($value)) {
            $value = Carbon::createFromTimestamp($value);
        }

        // If the value is in simple year, month, day format, we will format it using
        // that setup. This is for simple "date" fields which do not have hours on
        // the field. This conveniently picks up those dates and format correct.
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // If this value is some other type of string, we'll create the DateTime with
        // the format used by the database connection. Once we get the instance we
        // can return back the finally formatted DateTime instances to the devs.
        else {
            $value = Carbon::createFromFormat($format, $value);
        }

        return $value->format($format);
    }

    /**
     * Set the columns to be selected.
     * @param array|string $columns
     * @return static
     */
    public function select($columns)
    {
        $filter = new \CDbCriteria();
        $filter->select = $columns;

        $this->getDbCriteria()->mergeWith($filter);

        return $this;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Sync a single original attribute with its current value.
     *
     * @param  string $attribute
     * @return $this
     */
    public function syncOriginalAttribute($attribute)
    {
        $this->original[$attribute] = $this->attributes[$attribute];

        return $this;
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $value;
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp()
    {
        return new Carbon;
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();

        if (!$this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }

        if ($this->getIsNewRecord() && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        $defaults = [static::CREATED_AT, static::UPDATED_AT];

        return array_merge($this->dates, $defaults);
    }

    /**
     * Determine whether an attribute should be casted to a native type.
     *
     * @param  string $key
     * @return bool
     */
    protected function hasCast($key)
    {
        return array_key_exists($key, $this->casts);
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param  string $key
     * @return string
     */
    protected function getCastType($key)
    {
        return trim(strtolower($this->casts[$key]));
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'real':
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'object':
                return json_decode($value);
            case 'array':
            case 'json':
                return json_decode($value, true);
//            case 'collection':
//                return new BaseCollection(json_decode($value, true));
            default:
                return $value;
        }
    }

    /**
     * Creates an active record with the given attributes.
     * This method is internally used by the find methods.
     * @param array $attributes attribute values (column name=>column value)
     * @param boolean $callAfterFind whether to call {@link afterFind} after the record is populated.
     * @return static the newly created active record. The class of the object is the same as the model class.
     * Null is returned if the input data is false.
     */
    public function populateRecord($attributes, $callAfterFind = true)
    {
        if ($attributes !== false) {
            $record = $this->instantiate($attributes);
            $record->setScenario('update');
            $record->init();
            foreach ($attributes as $name => $value) {
                $record->setAttribute($name, $value);
            }
            $record->syncOriginal();
            $record->primaryKey = $record->getPrimaryKey();
            $record->attachBehaviors($record->behaviors());
            if ($callAfterFind) {
                $record->afterFind();
            }

            return $record;
        } else {
            return null;
        }
    }

    /**
     * Sets the named attribute value.
     * You may also use $this->AttributeName to set the attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @return boolean whether the attribute exists and the assignment is conducted successfully
     * @see hasAttribute
     */
    public function setAttribute($name, $value)
    {
        if (in_array($name, $this->getDates()) && $value) {
            $value = $this->fromDateTime($value);
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;
        } elseif (isset($this->getMetaData()->columns[$name])) {
            $this->attributes[$name] = $value;
        } else {
            return false;
        }

        return true;
    }

    /**
     * Returns all column attribute values.
     * Note, related objects are not returned.
     * @param mixed $names names of attributes whose value needs to be returned.
     * If this is true (default), then all attribute values will be returned, including
     * those that are not loaded from DB (null will be returned for those attributes).
     * If this is null, all attributes except those that are not loaded from DB will be returned.
     * @return array attribute values indexed by attribute names.
     */
    public function getAttributes($names = true)
    {
        $attributes = $this->attributes;
        foreach ($this->getMetaData()->columns as $name => $column) {
            if (property_exists($this, $name)) {
                $attributes[$name] = $this->$name;
            } elseif ($names === true && !isset($attributes[$name])) {
                $attributes[$name] = null;
            }
        }
        if (is_array($names)) {
            $attrs = array();
            foreach ($names as $name) {
                if (property_exists($this, $name)) {
                    $attrs[$name] = $this->$name;
                } else {
                    $attrs[$name] = isset($attributes[$name]) ? $attributes[$name] : null;
                }
            }

            return $attrs;
        } else {
            return $attributes;
        }
    }

    /**
     * Returns the named attribute value.
     * If this is a new record and the attribute is not set before,
     * the default column value will be returned.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * You may also use $this->AttributeName to obtain the attribute value.
     * @param string $name the attribute name
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute
     */
    public function getAttribute($name)
    {
        $value = null;
        if (property_exists($this, $name)) {
            $value = $this->$name;
        } elseif (isset($this->attributes[$name])) {
            $value = $this->attributes[$name];
        }

        // If the attribute exists within the cast array, we will convert it to
        // an appropriate native PHP type dependant upon the associated value
        // given with the key in the pair. Dayle made this comment line up.
        if ($this->hasCast($name)) {
            $value = $this->castAttribute($name, $value);
        }

        if (in_array($name, $this->getDates())) {
            if (!empty($value)) {
                return $this->asDateTime($value);
            }
        }

        return $value;
    }

    /**
     * Returns the old primary key value.
     * This refers to the primary key value that is populated into the record
     * after executing a find method (e.g. find(), findAll()).
     * The value remains unchanged even if the primary key attribute is manually assigned with a different value.
     * @return mixed the old primary key value. An array (column name=>column value) is returned if the primary key is composite.
     * If primary key is not defined, null will be returned.
     * @since 1.1.0
     */
    public function getOldPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Saves a selected list of attributes.
     * Unlike {@link save}, this method only saves the specified attributes
     * of an existing row dataset and does NOT call either {@link beforeSave} or {@link afterSave}.
     * Also note that this method does neither attribute filtering nor validation.
     * So do not use this method with untrusted data (such as user posted data).
     * You may consider the following alternative if you want to do so:
     * <pre>
     * $postRecord=Post::model()->findByPk($postID);
     * $postRecord->attributes=$_POST['post'];
     * $postRecord->save();
     * </pre>
     * @param array $attributes attributes to be updated. Each element represents an attribute name
     * or an attribute value indexed by its name. If the latter, the record's
     * attribute will be changed accordingly before saving.
     * @throws \CDbException if the record is new
     * @return boolean whether the update is successful. Note that false is also returned if the saving
     * was successfull but no attributes had changed and the database driver returns 0 for the number
     * of updated records.
     */
    public function saveAttributes($attributes)
    {
        if (!$this->getIsNewRecord()) {
            \Yii::trace(get_class($this) . '.saveAttributes()', 'system.db.ar.CActiveRecord');
            $values = array();
            foreach ($attributes as $name => $value) {
                if (is_integer($name)) {
                    $values[$value] = $this->$value;
                } else {
                    $values[$name] = $this->$name = $value;
                }
            }
            if ($this->primaryKey === null) {
                $this->primaryKey = $this->getPrimaryKey();
            }
            if ($this->updateByPk($this->getOldPrimaryKey(), $values) > 0) {
                $this->primaryKey = $this->getPrimaryKey();

                return true;
            } else {
                return false;
            }
        } else {
            throw new \CDbException(\Yii::t('yii', 'The active record cannot be updated because it is new.'));
        }
    }

    /**
     * Inserts a row into the table based on this active record attributes.
     * If the table's primary key is auto-incremental and is null before insertion,
     * it will be populated with the actual value after insertion.
     * Note, validation is not performed in this method. You may call {@link validate} to perform the validation.
     * After the record is inserted to DB successfully, its {@link isNewRecord} property will be set false,
     * and its {@link scenario} property will be set to be 'update'.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the attributes are valid and the record is inserted successfully.
     * @throws \CDbException if the record is not new
     */
    public function insert($attributes = null)
    {
        if (!$this->getIsNewRecord()) {
            throw new \CDbException(\Yii::t('yii',
                'The active record cannot be inserted to database because it is not new.'));
        }
        if ($this->beforeSave()) {
            \Yii::trace(get_class($this) . '.insert()', 'system.db.ar.CActiveRecord');
            $builder = $this->getCommandBuilder();
            $table = $this->getTableSchema();
            $command = $builder->createInsertCommand($table, $this->getAttributes($attributes));
            if ($command->execute()) {
                $primaryKey = $table->primaryKey;
                if ($table->sequenceName !== null) {
                    if (is_string($primaryKey) && $this->$primaryKey === null) {
                        $this->$primaryKey = $builder->getLastInsertID($table);
                    } elseif (is_array($primaryKey)) {
                        foreach ($primaryKey as $pk) {
                            if ($this->$pk === null) {
                                $this->$pk = $builder->getLastInsertID($table);
                                break;
                            }
                        }
                    }
                }
                $this->primaryKey = $this->getPrimaryKey();
                $this->afterSave();
                $this->setIsNewRecord(false);
                $this->setScenario('update');

                return true;
            }
        }

        return false;
    }

    /**
     * Updates the row represented by this active record.
     * All loaded attributes will be saved to the database.
     * Note, validation is not performed in this method. You may call {@link validate} to perform the validation.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the update is successful
     * @throws \CDbException if the record is new
     */
    public function update($attributes = null)
    {
        if ($this->getIsNewRecord()) {
            throw new \CDbException(\Yii::t('yii', 'The active record cannot be updated because it is new.'));
        }
        if ($this->beforeSave()) {
            \Yii::trace(get_class($this) . '.update()', 'system.db.ar.CActiveRecord');
            if ($this->primaryKey === null) {
                $this->primaryKey = $this->getPrimaryKey();
            }
            $this->updateByPk($this->getOldPrimaryKey(), $this->getAttributes($attributes));
            $this->primaryKey = $this->getPrimaryKey();
            $this->afterSave();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Saves the current record.
     *
     * The record is inserted as a row into the database table if its {@link isNewRecord}
     * property is true (usually the case when the record is created using the 'new'
     * operator). Otherwise, it will be used to update the corresponding row in the table
     * (usually the case if the record is obtained using one of those 'find' methods.)
     *
     * Validation will be performed before saving the record. If the validation fails,
     * the record will not be saved. You can call {@link getErrors()} to retrieve the
     * validation errors.
     *
     * If the record is saved via insertion, its {@link isNewRecord} property will be
     * set false, and its {@link scenario} property will be set to be 'update'.
     * And if its primary key is auto-incremental and is not set before insertion,
     * the primary key will be populated with the automatically generated key value.
     *
     * @param boolean $runValidation whether to perform validation before saving the record.
     * If the validation fails, the record will not be saved to database.
     * @param array $attributes list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the saving succeeds
     */
    public function save($runValidation = true, $attributes = null)
    {
        if (!$runValidation || $this->validate($attributes)) {
            return $this->getIsNewRecord() ? $this->insert($attributes) : $this->update($attributes);
        } else {
            return false;
        }
    }

    /**
     * Repopulates this active record with the latest data.
     * @return boolean whether the row still exists in the database. If true, the latest data will be populated to this active record.
     */
    public function refresh()
    {
        \Yii::trace(get_class($this) . '.refresh()', 'system.db.ar.CActiveRecord');
        if (($record = $this->findByPk($this->getPrimaryKey())) !== null) {
            $this->attributes = [];
            $this->relations = [];
            foreach ($this->getMetaData()->columns as $name => $column) {
                if (property_exists($this, $name)) {
                    $this->$name = $record->$name;
                } else {
                    $this->attributes[$name] = $record->$name;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * PHP getter magic method.
     * This method is overridden so that AR attributes can be accessed like properties.
     * @param string $name property name
     * @return mixed property value
     * @see getAttribute
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->getAttribute($name);
        } elseif (isset($this->getMetaData()->columns[$name])) {
            return null;
        } elseif (isset($this->relations[$name])) {
            return $this->relations[$name];
        } elseif (isset($this->getMetaData()->relations[$name])) {
            return $this->getRelated($name);
        } else {
            return parent::__get($name);
        }
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that AR attributes can be accessed like properties.
     * @param string $name property name
     * @param mixed $value property value
     * @return void
     */
    public function __set($name, $value)
    {
        if ($this->setAttribute($name, $value) === false) {
            if (isset($this->getMetaData()->relations[$name])) {
                $this->relations[$name] = $value;
            } else {
                parent::__set($name, $value);
            }
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking
     * if the named attribute is null or not.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     */
    public function __isset($name)
    {
        if (isset($this->attributes[$name])) {
            return true;
        } elseif (isset($this->getMetaData()->columns[$name])) {
            return false;
        } elseif (isset($this->relations[$name])) {
            return true;
        } elseif (isset($this->getMetaData()->relations[$name])) {
            return $this->getRelated($name) !== null;
        } /*else {
            $class = get_parent_class(get_parent_class($this));
            dd($class,12);
            return $class::__isset($name);
//            return parent::__isset($name);
        }*/
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Sets a component property to be null.
     * This method overrides the parent implementation by clearing
     * the specified attribute value.
     * @param string $name the property name or the event name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->getMetaData()->columns[$name])) {
            unset($this->attributes[$name]);
        } elseif (isset($this->getMetaData()->relations[$name])) {
            unset($this->relations[$name]);
        } else {
            parent::__unset($name);
        }
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * @param  array|string|null $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        $dirty = $this->getDirty();

        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        if (!is_array($attributes)) {
            $attributes = func_get_args();
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @param  string $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        return is_numeric($current) && is_numeric($original) && strcmp((string)$current, (string)$original) === 0;
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                !$this->originalIsNumericallyEquivalent($key)
            ) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Get the model's original attribute values.
     *
     * @param  string $key
     * @param  mixed $default
     * @return array
     */
    public function getOriginal($key = null, $default = null)
    {
        return array_get($this->original, $key, $default);
    }

    /**
     * Do not call this method. This method is used internally by {@link CActiveFinder} to populate
     * related objects. This method adds a related object to this record.
     * @param string $name attribute name
     * @param mixed $record the related record
     * @param mixed $index the index value in the related object collection.
     * If true, it means using zero-based integer index.
     * If false, it means a HAS_ONE or BELONGS_TO object and no index is needed.
     */
    public function addRelatedRecord($name, $record, $index)
    {
        if ($index !== false) {
            if (!isset($this->relations[$name])) {
                $this->relations[$name] = array();
            }
            if ($record instanceof \CActiveRecord) {
                if ($index === true) {
                    $this->relations[$name][] = $record;
                } else {
                    $this->relations[$name][$index] = $record;
                }
            }
        } elseif (!isset($this->relations[$name])) {
            $this->relations[$name] = $record;
        }
    }

    /**
     * Returns the related record(s).
     * This method will return the related record(s) of the current record.
     * If the relation is HAS_ONE or BELONGS_TO, it will return a single object
     * or null if the object does not exist.
     * If the relation is HAS_MANY or MANY_MANY, it will return an array of objects
     * or an empty array.
     * @param string $name the relation name (see {@link relations})
     * @param boolean $refresh whether to reload the related objects from database. Defaults to false.
     * If the current record is not a new record and it does not have the related objects loaded they
     * will be retrieved from the database even if this is set to false.
     * If the current record is a new record and this value is false, the related objects will not be
     * retrieved from the database.
     * @param mixed $params array or CDbCriteria object with additional parameters that customize the query conditions as specified in the relation declaration.
     * If this is supplied the related record(s) will be retrieved from the database regardless of the value or {@link $refresh}.
     * The related record(s) retrieved when this is supplied will only be returned by this method and will not be loaded into the current record's relation.
     * The value of the relation prior to running this method will still be available for the current record if this is supplied.
     * @return mixed the related object(s).
     * @throws \CDbException if the relation is not specified in {@link relations}.
     */
    public function getRelated($name, $refresh = false, $params = array())
    {
        if (!$refresh && $params === array() && (isset($this->relations[$name]) || array_key_exists($name,
                    $this->relations))
        ) {
            return $this->relations[$name];
        }

        $md = $this->getMetaData();
        if (!isset($md->relations[$name])) {
            throw new \CDbException(Yii::t('yii', '{class} does not have relation "{name}".',
                array('{class}' => get_class($this), '{name}' => $name)));
        }

        \Yii::trace('lazy loading ' . get_class($this) . '.' . $name, 'system.db.ar.CActiveRecord');
        $relation = $md->relations[$name];
        if ($this->getIsNewRecord() && !$refresh && ($relation instanceof \CHasOneRelation || $relation instanceof \CHasManyRelation)) {
            return $relation instanceof \CHasOneRelation ? null : array();
        }

        if ($params !== array()) // dynamic query
        {
            $exists = isset($this->relations[$name]) || array_key_exists($name, $this->relations);
            if ($exists) {
                $save = $this->relations[$name];
            }

            if ($params instanceof \CDbCriteria) {
                $params = $params->toArray();
            }

            $r = array($name => $params);
        } else {
            $r = $name;
        }
        unset($this->relations[$name]);

        $finder = $this->getActiveFinder($r);
        $finder->lazyFind($this);

        if (!isset($this->relations[$name])) {
            if ($relation instanceof \CHasManyRelation) {
                $this->relations[$name] = array();
            } elseif ($relation instanceof \CStatRelation) {
                $this->relations[$name] = $relation->defaultValue;
            } else {
                $this->relations[$name] = null;
            }
        }

        if ($params !== array()) {
            $results = $this->relations[$name];
            if ($exists) {
                $this->relations[$name] = $save;
            } else {
                unset($this->relations[$name]);
            }

            return $results;
        } else {
            return $this->relations[$name];
        }
    }

    /**
     * Returns a value indicating whether the named related object(s) has been loaded.
     * @param string $name the relation name
     * @return boolean a value indicating whether the named related object(s) has been loaded.
     */
    public function hasRelated($name)
    {
        return isset($this->relations[$name]) || array_key_exists($name, $this->relations);
    }

    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed   $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Update the model's update timestamp.
     *
     * @return bool
     */
    public function touch()
    {
        if (!$this->timestamps) {
            return false;
        }

        $this->updateTimestamps();

        return $this->save();
    }

    /**
     * Touch the owning relations of the model.
     *
     * @return void
     */
    public function touchOwners()
    {
        foreach ($this->touches as $relation) {
            if ($this->$relation instanceof self) {
                $this->$relation->touch();
                $this->$relation->touchOwners();
            } elseif (is_array($this->$relation)) {
                foreach ($this->$relation as $relation) {
                    $relation->touchOwners();
                }
            }
        }
    }

    protected function afterSave()
    {
        $this->touchOwners();
        parent::afterSave();
    }

    protected function afterDelete()
    {
        $this->touchOwners();
        parent::afterDelete();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributesToArray();

        return array_merge($attributes, $this->relationsToArray());
    }

    /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];

        foreach ($this->getArrayableRelations() as $key => $value) {
            // If the values implements the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.

            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }

            //@todo temporary until we implement Collections
            elseif (is_array($value)) {
                $relation = array_map(function($model) {
                    return $model->toArray();
                }, $value);
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes since null is used to represent empty relationships if
            // if it a has one or belongs to type relationships on the models.
            elseif (is_null($value)) {
                $relation = $value;
            }

            // If the relationships snake-casing is enabled, we will snake case this
            // key so that the relation attribute is snake cased in this returned
            // array to the developers, making this consistent with attributes.
            if (static::$snakeAttributes) {
                $key = Str::snake($key);
            }

            // If the relation value has been set, we will set it on this attributes
            // list for returning. If it was not arrayable or null, we'll not set
            // the value on the array because it is some type of invalid value.
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }

            unset($relation);
        }

        return $attributes;
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getArrayableAttributes();

        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        $mutatedAttributes = $this->getMutatedAttributes();

        // We want to spin through all the mutated attributes for this model and call
        // the mutator for the attribute. We cache off every mutated attributes so
        // we don't have to constantly check on attributes that actually change.
        foreach ($mutatedAttributes as $key) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        foreach ($this->casts as $key => $value) {
            if (! array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)) {
                continue;
            }

            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );
        }

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }

        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Get an attribute array of all arrayable relations.
     *
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->getArrayableItems($this->attributes);
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array  $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            return array_intersect_key($values, array_flip($this->getVisible()));
        }

        return array_diff_key($values, array_flip($this->getHidden()));
    }

    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
     *
     * @param  array  $hidden
     * @return void
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Add hidden attributes for the model.
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addHidden($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->hidden = array_merge($this->hidden, $attributes);
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     *
     * @param  array  $visible
     * @return void
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;
    }

    /**
     * Add visible attributes for the model.
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addVisible($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->visible = array_merge($this->visible, $attributes);
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        $value = $this->mutateAttribute($key, $value);

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Get the mutated attributes for a given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = get_class($this);

        if (! isset(static::$mutatorCache[$class])) {
            static::cacheMutatedAttributes($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
     *
     * @param string $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        $mutatedAttributes = [];

        // Here we will extract all of the mutated attributes so that we can quickly
        // spin through them after we export models to their array form, which we
        // need to be fast. This'll let us know the attributes that can mutate.
        foreach (get_class_methods($class) as $method) {
            if (strpos($method, 'Attribute') !== false &&
                preg_match('/^get(.+)Attribute$/', $method, $matches)) {
                if (static::$snakeAttributes) {
                    $matches[1] = Str::snake($matches[1]);
                }

                $mutatedAttributes[] = lcfirst($matches[1]);
            }
        }

        static::$mutatorCache[$class] = $mutatedAttributes;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    protected function beforeSave()
    {
        $this->setDbMode('write');
        return true;
    }

    protected function beforeDelete()
    {
        $this->setDbMode('write');
        return true;
    }

    protected function beforeFind()
    {
        $this->setDbMode();
        return true;
    }

    protected function beforeCount()
    {
        $this->setDbMode();
        return true;
    }

    protected function setDbMode($mode = 'read')
    {
        if (!property_exists($this->getDbConnection(), 'readConnectionString')) {
            return;
        }

        if (!$this->getDbConnection()->isReadWrite()) {
            \Yii::trace("Read-write mode is not available");
            return;
        }

        $db = $this->getDbConnection();

        /**
         * @var $db DbConnection
         */
        if ($mode == 'write') {
            \Yii::trace('Write mode');
            if (self::$writeDb) {
                return self::$db = self::$writeDb;
            }
            $conn = clone $db;
            $conn->setWriteMode();
            self::$writeDb = $conn;
            return self::$db = self::$writeDb;
        }

        \Yii::trace('Read mode');

        if (self::$readDb) {
            return self::$db = self::$readDb;
        }

        self::$readDb = app('db');
        self::$db = self::$readDb;
    }
}