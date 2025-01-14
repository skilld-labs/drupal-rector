<?php

namespace DrupalRector\Rector\ValueObject;

class GetMockConfiguration {

    private string $fullyQualifiedClassName;

    /**
     * @param string $fullyQualifiedClassName
     */
    public function __construct(string $fullyQualifiedClassName) {
        $this->fullyQualifiedClassName = $fullyQualifiedClassName;
    }

    public function getFullyQualifiedClassName(): string {
        return $this->fullyQualifiedClassName;
    }

}
