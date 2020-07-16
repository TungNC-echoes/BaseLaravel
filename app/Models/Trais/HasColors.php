<?php

namespace App\Models\Traits;

use App\Models\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use InvalidArgumentException;

trait HasColors
{
    protected $queuedColors = [];

    public static function getColorClassName(): string
    {
        return Color::class;
    }

    public static function bootHasColors()
    {
        static::created(function (Model $colorableModel) {
            if (count($colorableModel->queuedColors) > 0) {
                $colorableModel->attachColors($colorableModel->queuedColors);

                $colorableModel->queuedColors = [];
            }
        });

        static::deleted(function (Model $deletedModel) {
            $colors = $deletedModel->colors()->get();

            $deletedModel->detachColors($colors);
        });
    }

    public function colors(): MorphToMany
    {
        return $this->morphToMany(self::getColorClassName(), 'colorable');
    }

    /**
     * @param string|array|\ArrayAccess|Color $colors
     */
    public function setColorsAttribute($colors)
    {
        if (! $this->exists) {
            $this->queuedColors = $colors;

            return;
        }

        $this->attachColors($colors);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|\ArrayAccess|Color $colors
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAllColors(Builder $query, $colors, string $type = null): Builder
    {
        $colors = static::convertToColors($colors, $type);

        collect($colors)->each(function ($color) use ($query) {
            $query->whereHas('colors', function (Builder $query) use ($color) {
                $query->where('colors.id', $color ? $color->id : 0);
            });
        });

        return $query;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|\ArrayAccess|Color $colors
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAnyColors(Builder $query, $colors, string $type = null): Builder
    {
        $colors = static::convertToColors($colors, $type);

        return $query->whereHas('colors', function (Builder $query) use ($colors) {
            $colorIds = collect($colors)->pluck('id');

            $query->whereIn('colors.id', $colorIds);
        });
    }

    public function scopeWithAllColorsOfAnyType(Builder $query, $colors): Builder
    {
        $colors = static::convertToColorsOfAnyType($colors);

        collect($colors)->each(function ($color) use ($query) {
            $query->whereHas('colors', function (Builder $query) use ($color) {
                $query->where('colors.id', $color ? $color->id : 0);
            });
        });

        return $query;
    }

    public function scopeWithAnyColorsOfAnyType(Builder $query, $colors): Builder
    {
        $colors = static::convertToColorsOfAnyType($colors);

        return $query->whereHas('colors', function (Builder $query) use ($colors) {
            $colorIds = collect($colors)->pluck('id');

            $query->whereIn('colors.id', $colorIds);
        });
    }

    public function colorsWithType(string $type = null): Collection
    {
        return $this->colors->filter(function (Color $color) use ($type) {
            return $color->type === $type;
        });
    }

    /**
     * @param array|\ArrayAccess|Color $colors
     *
     * @return $this
     */
    public function attachColors($colors)
    {
        $className = static::getColorClassName();

        $colors = collect($className::findOrCreate($colors));

        $this->colors()->syncWithoutDetaching($colors->pluck('id')->toArray());

        return $this;
    }

    /**
     * @param string|Color $color
     *
     * @return $this
     */
    public function attachColor($color)
    {
        return $this->attachColors([$color]);
    }

    /**
     * @param array|\ArrayAccess $colors
     *
     * @return $this
     */
    public function detachColors($colors)
    {
        $colors = static::convertToColors($colors);

        collect($colors)
            ->filter()
            ->each(function (Color $color) {
                $this->colors()->detach($color);
            });

        return $this;
    }

    /**
     * @param string|Color $color
     *
     * @return $this
     */
    public function detachColor($color)
    {
        return $this->detachColors([$color]);
    }

    /**
     * @param array|\ArrayAccess $colors
     *
     * @return $this
     */
    public function syncColors($colors)
    {
        $className = static::getColorClassName();

        $colors = collect($className::findOrCreate($colors));

        $this->colors()->sync($colors->pluck('id')->toArray());

        return $this;
    }

    /**
     * @param array|\ArrayAccess $colors
     * @param string|null $type
     *
     * @return $this
     */
    public function syncColorsWithType($colors, string $type = null)
    {
        $className = static::getColorClassName();

        $colors = collect($className::findOrCreate($colors, $type));

        $this->syncColorIds($colors->pluck('id')->toArray(), $type);

        return $this;
    }

    protected static function convertToColors($values, $type = null)
    {
        return collect($values)->map(function ($value) use ($type) {
            if ($value instanceof Color) {
                if (isset($type) && $value->type != $type) {
                    throw new InvalidArgumentException("Type was set to {$type} but color is of type {$value->type}");
                }

                return $value;
            }

            $className = static::getColorClassName();

            return $className::findFromString($value, $type);
        });
    }

    protected static function convertToColorsOfAnyType($values)
    {
        return collect($values)->map(function ($value) {
            if ($value instanceof Color) {
                return $value;
            }

            $className = static::getColorClassName();

            return $className::findFromStringOfAnyType($value);
        });
    }

    /**
     * Use in place of eloquent's sync() method so that the color type may be optionally specified.
     *
     * @param $ids
     * @param string|null $type
     * @param bool $detaching
     */
    protected function syncColorIds($ids, string $type = null, $detaching = true)
    {
        $isUpdated = false;

        // Get a list of color_ids for all current colors
        $current = $this->colors()
            ->newPivotStatement()
            ->where('colorable_id', $this->getKey())
            ->where('colorable_type', $this->getMorphClass())
            ->when($type !== null, function ($query) use ($type) {
                $colorModel = $this->colors()->getRelated();

                return $query->join(
                    $colorModel->getTable(),
                    'colorables.color_id',
                    '=',
                    $colorModel->getTable().'.'.$colorModel->getKeyName()
                )
                    ->where('colors.type', $type);
            })
            ->pluck('color_id')
            ->all();

        // Compare to the list of ids given to find the colors to remove
        $detach = array_diff($current, $ids);
        if ($detaching && count($detach) > 0) {
            $this->colors()->detach($detach);
            $isUpdated = true;
        }

        // Attach any new ids
        $attach = array_diff($ids, $current);
        if (count($attach) > 0) {
            collect($attach)->each(function ($id) {
                $this->colors()->attach($id, []);
            });
            $isUpdated = true;
        }

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if ($isUpdated) {
            $this->colors()->touchIfTouching();
        }
    }
}
