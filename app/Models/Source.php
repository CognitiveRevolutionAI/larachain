<?php

namespace App\Models;

use App\Exceptions\SourceTypeMissingException;
use App\Source\SourceEnum;
use App\Source\Types\BaseSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array|null $meta_data
 * @property int $project_id
 * @property int $order
 * @property SourceEnum $type
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Source extends BaseTypeModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta_data' => 'encrypted:array',
        'type' => SourceEnum::class,
    ];

    protected $appends = [
        'type_formatted',
    ];

    protected function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @throws SourceTypeMissingException
     */
    public function run()
    {
        try {
            $statusTypes = config('larachain.sources');
            $statusType = $this->type->value;
            $statusType = data_get($statusTypes, $statusType);
            $class = data_get($statusType, 'class', null);
            if (! $class) {
                throw new \Exception('Source Missing Class');
            }

            logger('Running Source '.$class);
            //@TODO make this check in uses BaseSourceType
            $sourceType = app($class, [
                'source' => $this,
            ]);
            /** @var BaseSourceType $sourceType */
            return $sourceType->handle();
        } catch (\Exception $e) {
            //@TODO This exception needs to be more specific
            logger($e);
            throw new SourceTypeMissingException();
        }
    }

    public function getTypeFormattedAttribute()
    {
        return str($this->type->value)->headline();
    }
}
