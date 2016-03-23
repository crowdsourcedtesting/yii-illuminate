<?php
namespace CST\Yii\Illuminate\Database;

trait SoftDeletesTrait
{
    protected $forceDelete = false;
    protected $withTrashed = false;

    /**
     * Override that does a soft-delete (or optionally a normal/hard delete)
     * @return bool if operation succeeded
     */
    public function delete()
    {
        if (!$this->beforeDelete()) {
            return false;
        }

        if ($this->forceDelete === true) {
            return parent::delete();
        }

        if (empty( $this->{$this->getDeletedAtColumn()} )) {
            $this->{$this->getDeletedAtColumn()} = date('Y-m-d H:i:s', time());
            return $this->save();
        } else {
            return true;
        }
    }

    /**
     * Reverts a soft-delete operation
     * @return bool if operation succeeded
     */
    public function restore()
    {
        $this->{$this->getDeletedAtColumn()} = null;
        return $this->save();
    }

    /**
     * Default scope to exclude soft-deleted instances
     * @return mixed
     */
    public function defaultScope()
    {
        return [
            'condition' => '`'. $this->getDeletedAtColumn() .'` IS NULL',
        ];
    }

    /**
     * Scope to include soft-deleted instances
     * @return mixed
     */
    public function withTrashed()
    {
        $this->getDbCriteria()->mergeWith([
            'condition' => '`'. $this->getDeletedAtColumn() .'` IS NOT NULL',
        ], false);
        return $this;
    }

    /**
     * Scope to restrict to only soft-deleted instances
     * @return mixed
     */
    public function trashed()
    {
        $criteria = $this->getDbCriteria();
        $criteria->condition = '';
        $criteria->mergeWith([ 'condition' => '`'. $this->getDeletedAtColumn() .'` IS NOT NULL' ]);
        return $this;
    }

    /**
     * Checks if an instance is soft-deleted
     * @return bool
     */
    public function isTrashed()
    {
        return !empty($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }
}