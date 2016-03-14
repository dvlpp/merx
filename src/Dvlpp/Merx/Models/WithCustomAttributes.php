<?php

namespace Dvlpp\Merx\Models;

use Illuminate\Support\Collection;

trait WithCustomAttributes
{

    /**
     * @param string $name
     * @return string|null
     */
    public function customAttribute($name)
    {
        return $this->hasCustomAttribute($name)
            ? $this->custom_attributes[$name]
            : null;
    }

    /**
     * @param  $name
     * @return boolean 
     */
    public function hasCustomAttribute($name)
    {   
        return isset($this->custom_attributes[$name]);
    }

    /**
     * @return Collection
     */
    public function allCustomAttributes()
    {
        return collect($this->custom_attributes);
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $save
     */
    public function setCustomAttribute($name, $value, $save = true)
    {
        $attrs = $this->custom_attributes;
        $attrs[$name] = $value;
        $this->custom_attributes = $attrs;

        if ($save) {
            $this->save();
        }
    }

    /**
     * @param array $attributes
     */
    public function setMultipleCustomAttributes(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->setCustomAttribute($attribute, $value, false);
        }

        $this->save();
    }

    /**
     * @param $name
     * @param bool $save
     */
    public function removeCustomAttribute($name, $save = true)
    {
        $attrs = $this->custom_attributes;
        unset($attrs[$name]);
        $this->custom_attributes = $attrs;

        if ($save) {
            $this->save();
        }
    }

    /**
     * @param array $attributes
     */
    public function removeMultipleCustomAttributes(array $attributes)
    {
        foreach ($attributes as $attribute) {
            $this->removeCustomAttribute($attribute, false);
        }

        $this->save();
    }
}