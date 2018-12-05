<?php

namespace Extensions\Bundle\OptionImporterConnectorBundle\ArrayConverter\StandardToFlat;

use Pim\Component\Connector\ArrayConverter\ArrayConverterInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Akeneo\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Pim\Component\Catalog\AttributeTypes;
use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Akeneo\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Item\DataInvalidItem;

/**
 * Class Product
 *
 * @author                 Nicolas SOUFFLEUR, Akeneo Expert <contact@nicolas-souffleur.com>
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Product implements ArrayConverterInterface
{

    /** @var ArrayConverterInterface */
    protected $productConverter;
    /**
     * @var IdentifiableObjectRepositoryInterface $repository
     */
    protected $repository;
    /**
     * @var SimpleFactoryInterface $factory
     */
    protected $factory;
    /**
     * @var AttributeRepositoryInterface $attributeRepository
     */
    protected $attributeRepository;
    /**
     * @var BulkSaverInterface $saver
     */
    protected $saver;
    /**
     * @var SimpleFactoryInterface $factoryOptionValue
     */
    protected $factoryOptionValue;

    /**
     * @param ArrayConverterInterface               $productConverter
     * @param IdentifiableObjectRepositoryInterface $repository
     * @param SimpleFactoryInterface                $factory
     * @param AttributeRepositoryInterface          $attributeRepository
     * @param BulkSaverInterface                    $saver
     * @param SimpleFactoryInterface                $factoryOptionValue
     */
    public function __construct(ArrayConverterInterface $productConverter, IdentifiableObjectRepositoryInterface $repository, SimpleFactoryInterface $factory, AttributeRepositoryInterface $attributeRepository, BulkSaverInterface $saver, SimpleFactoryInterface $factoryOptionValue)
    {
        $this->productConverter    = $productConverter;
        $this->repository          = $repository;
        $this->factory             = $factory;
        $this->attributeRepository = $attributeRepository;
        $this->saver               = $saver;
        $this->factoryOptionValue  = $factoryOptionValue;
    }

    /**
     * @param array $item
     * @param array $options
     *
     * @return array
     */
    public function convert(array $item, array $options = [])
    {
        if (null != $item) {
            $attributeTypes = $this->attributeRepository->getAttributeTypeByCodes(array_keys($item));
            foreach ($item as $attribute => $value) {
                if (!array_key_exists($attribute, $attributeTypes) || null === $value || empty($value)) {
                    continue;
                }
                if ($attributeTypes[$attribute] == AttributeTypes::OPTION_SIMPLE_SELECT) {
                    $item[$attribute] = $this->createOptionByValueType($value, $attribute, $item, $options['default_locale'], false);
                }
                if ($attributeTypes[$attribute] == AttributeTypes::OPTION_MULTI_SELECT) {
                    $item[$attribute] = $this->createOptionByValueType($value, $attribute, $item, $options['default_locale'], true);
                }
            }
        }

        $convertedItem = $this->productConverter->convert($item, $options);

        return $convertedItem;
    }

    /**
     * @param $optionCode
     * @param $value
     * @param $attribute
     * @param $locale
     * @param $item
     *
     * @return mixed
     */
    public function saveAttributeOption($optionCode, $value, $attribute, $locale, $item)
    {
        $attributeOptionItemArray = $this->createAttributeOptionItemArray($optionCode, $value, $attribute, $locale);

        try {
            $this->findOrCreateAttributeOption($attributeOptionItemArray);
        } catch (\InvalidArgumentException $exception) {
            $this->skipItemWithMessage($item, $exception->getMessage(), $exception);
        }

        return $optionCode;
    }

    /**
     * @param $item
     *
     * @return object
     */
    protected function findOrCreateAttributeOption($item)
    {
        $attribute = $this->attributeRepository->findOneBy(['code' => $item["attribute"]]);

        $option = $this->repository->findOneBy([
            'code'      => $item["code"],
            'attribute' => $attribute->getId()
        ]);

        if (null == $option) {
            $option = $this->factory->create();
            $option->setCode($item["code"]);
            $option->setAttribute($attribute);

            $optionValue = $this->factoryOptionValue->create();
            $optionValue->setLocale($item["locale"]);
            $optionValue->setLabel($item["label"]);

            $option->addOptionValue($optionValue);

            $optionValue->setOption($option);

            $this->saver->save($option);
        }

        return $option;
    }

    /**
     * Determine if the attribute is a multi-select or a simple select
     *
     * @param $value
     * @param $attribute
     * @param $item
     * @param $locale
     * @param $isMulti
     *
     * @return mixed
     */
    public function createOptionByValueType($value, $attribute, $item, $locale, $isMulti)
    {
        $attributeOptions = null;
        $searchString     = ',';

        if ($isMulti && strpos($value, $searchString) !== false) {
            $labels = explode(',', $value);
            $codes  = array();

            foreach ($labels as $label) {
                $optionCode = $this->formatOptionCode($label);
                $code       = $this->saveAttributeOption($optionCode, $label, $attribute, $locale, $item);
                array_push($codes, $code);
            }

            $attributeOptions = implode(",", $codes);
        } else {
            $optionCode       = $this->formatOptionCode($value);
            $attributeOptions = $this->saveAttributeOption($optionCode, $value, $attribute, $locale, $item);
        }

        return $attributeOptions;
    }

    /**
     * Format the attribute option code
     *
     * @param string $code
     *
     * @return string
     */
    public function formatOptionCode($code)
    {
        $urlizer = new Urlizer();
        $code    = $urlizer->urlize($code);
        $code    = str_replace('-', '_', $code);

        return $code;
    }

    /**
     * Build an array with all the infos for the creation
     *
     * @param string $optionCode
     * @param string $value
     * @param string $attributeCode
     *
     * @return array
     */
    public function createAttributeOptionItemArray($optionCode, $value, $attributeCode, $locale)
    {
        $option              = [];
        $option["attribute"] = $attributeCode;
        $option["code"]      = $optionCode;
        $option["label"]     = $value;
        $option["locale"]    = $locale;

        return $option;
    }
}