<?php

namespace Dvlpp\Merx\Models;

trait WithCustomAttributes
{

    /**
     * @param string $name
     * @return string|null
     */
    public function customAttribute($name, $value = null)
    {
        return isset($this->custom_attributes[$name])
            ? $this->custom_attributes[$name]
            : null;
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

    public function setMultipleCustomAttribute(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->setCustomAttribute($attribute, $value, false);
        }

        $this->save();
    }
}