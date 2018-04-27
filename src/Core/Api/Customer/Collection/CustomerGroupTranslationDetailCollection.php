<?php declare(strict_types=1);

namespace Shopware\Api\Customer\Collection;

use Shopware\Api\Customer\Struct\CustomerGroupTranslationDetailStruct;
use Shopware\Api\Language\Collection\LanguageBasicCollection;

class CustomerGroupTranslationDetailCollection extends CustomerGroupTranslationBasicCollection
{
    /**
     * @var CustomerGroupTranslationDetailStruct[]
     */
    protected $elements = [];

    public function getCustomerGroups(): CustomerGroupBasicCollection
    {
        return new CustomerGroupBasicCollection(
            $this->fmap(function (CustomerGroupTranslationDetailStruct $customerGroupTranslation) {
                return $customerGroupTranslation->getCustomerGroup();
            })
        );
    }

    public function getLanguages(): LanguageBasicCollection
    {
        return new LanguageBasicCollection(
            $this->fmap(function (CustomerGroupTranslationDetailStruct $customerGroupTranslation) {
                return $customerGroupTranslation->getLanguage();
            })
        );
    }

    protected function getExpectedClass(): string
    {
        return CustomerGroupTranslationDetailStruct::class;
    }
}