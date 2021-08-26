<?php
namespace Hyperwallet\Tests\Model;

use Hyperwallet\Model\BankAccount;
use Hyperwallet\Model\Payment;
use Hyperwallet\Model\PrepaidCard;
use Hyperwallet\Model\User;
use Hyperwallet\Model\WebhookNotification;

class WebhookNotificationTest extends ModelTestCase {

    protected function getModelName() {
        return 'WebhookNotification';
    }

    /**
     * @dataProvider ignoredPropertiesProvider
     *
     * @param string $property The property to look for
     */
    public function testGettersForIgnoredProperties($property) {
        $this->performGettersForIgnoredPropertiesTest($property);
    }

    /**
     * @dataProvider propertiesProvider
     *
     * @param string $property The property to look for
     */
    public function testGetterReturnValueIsSet($property) {
        $this->performGetterReturnValueIsSetTest($property);
    }

    /**
     * @dataProvider propertiesProvider
     *
     * @param string $property The property to look for
     */
    public function testGetterReturnValueIsNotSet($property) {
        $this->performGetterReturnValueIsNotSetTest($property);
    }

    /**
     * @dataProvider notificationTypeProvider
     *
     * @param string $type The notification type
     * @param object $clazz The expected class type
     *
     */
    public function testConstructorObjectConversion($type, $clazz) {
        $data = array(
            'type' => $type,
            'test2' => 'value2',
            'object' => array(
                'test' => 'value'
            )
        );

        $notification = new WebhookNotification($data);
        if ($clazz === null) {
            $this->assertNull($notification->getObject());
        } else {
            $this->assertNotNull($notification->getObject());
            $this->assertInstanceOf($clazz, $notification->getObject());

            $this->assertEquals(array(
                'test' => 'value'
            ), $notification->getObject()->getProperties());
        }
    }

    public function notificationTypeProvider() {
        return array(
            'USERS.CREATED' => array('USERS.CREATED', User::class),
            'USERS.UPDATED.STATUS.ACTIVATED' => array('USERS.UPDATED.STATUS.ACTIVATED', User::class),
            'USERS.UPDATED.STATUS.LOCKED' => array('USERS.UPDATED.STATUS.LOCKED', User::class),
            'USERS.UPDATED.STATUS.FROZEN' => array('USERS.UPDATED.STATUS.FROZEN', User::class),
            'USERS.UPDATED.STATUS.DE_ACTIVATED' => array('USERS.UPDATED.STATUS.DE_ACTIVATED', User::class),

            'USERS.BANK_ACCOUNTS.CREATED' => array('USERS.BANK_ACCOUNTS.CREATED', BankAccount::class),
            'USERS.BANK_ACCOUNTS.UPDATED.STATUS.ACTIVATED' => array('USERS.BANK_ACCOUNTS.UPDATED.STATUS.ACTIVATED', BankAccount::class),
            'USERS.BANK_ACCOUNTS.UPDATED.STATUS.INVALID' => array('USERS.BANK_ACCOUNTS.UPDATED.STATUS.INVALID', BankAccount::class),
            'USERS.BANK_ACCOUNTS.UPDATED.STATUS.DE_ACTIVATED' => array('USERS.BANK_ACCOUNTS.UPDATED.STATUS.DE_ACTIVATED', BankAccount::class),

            'USERS.PREPAID_CARDS.CREATED' => array('USERS.PREPAID_CARDS.CREATED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.QUEUED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.QUEUED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.PRE_ACTIVATED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.PRE_ACTIVATED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.ACTIVATED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.ACTIVATED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.DECLINED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.DECLINED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.LOCKED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.LOCKED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.SUSPENDED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.SUSPENDED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.LOST_OR_STOLEN' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.LOST_OR_STOLEN', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.DE_ACTIVATED' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.DE_ACTIVATED', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.COMPLIANCE_HOLD' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.COMPLIANCE_HOLD', PrepaidCard::class),
            'USERS.PREPAID_CARDS.UPDATED.STATUS.KYC_HOLD' => array('USERS.PREPAID_CARDS.UPDATED.STATUS.KYC_HOLD', PrepaidCard::class),

            'PAYMENTS.CREATED' => array('PAYMENTS.CREATED', Payment::class),

            'TEST' => array('TEST', null),
        );
    }

}
