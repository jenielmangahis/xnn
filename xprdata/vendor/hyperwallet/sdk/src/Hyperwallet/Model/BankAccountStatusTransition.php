<?php
namespace Hyperwallet\Model;

/**
 * Represents a V3 Bank Account Status Transition
 *
 * @package Hyperwallet\Model
 */
class BankAccountStatusTransition extends StatusTransition {

    const TRANSITION_DE_ACTIVATED = 'DE_ACTIVATED';

    /**
     * Creates a instance of BankAccountStatusTransition
     *
     * @param string[] $properties The default properties
     */
    public function __construct(array $properties = array()) {
        parent::__construct($properties);
    }

}