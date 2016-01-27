<?php

namespace Dvlpp\Merx\Models;

trait WithCustomAttributes
{

    /**
     * @param string $name
     * @param string|null $value
     * @return string|null
     */
    public function attribute($name, $value = null)
    {
        if (!$value) {
            return $this->custom_attributes[$name];
        }

        // Setter case
        $attrs = $this->custom_attributes;
        $attrs[$name] = $value;
        $this->custom_attributes = $attrs;
    }
}