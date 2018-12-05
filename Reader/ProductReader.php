<?php
namespace Extensions\Bundle\OptionImporterConnectorBundle\Reader;

use Pim\Component\Connector\Reader\File\Csv\ProductReader as BaseReader;
use Pim\Component\Connector\Exception\DataArrayConversionException;

/**
 * Class ProductReader
 *
 * @author                 Nicolas SOUFFLEUR, Akeneo Expert <contact@nicolas-souffleur.com>
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductReader extends BaseReader
{

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $jobParameters = $this->stepExecution->getJobParameters();
        $filePath      = $jobParameters->get('filePath');
        if (null === $this->fileIterator) {
            $delimiter          = $jobParameters->get('delimiter');
            $enclosure          = $jobParameters->get('enclosure');
            $defaultOptions     = [
                'reader_options' => [
                    'fieldDelimiter' => $delimiter,
                    'fieldEnclosure' => $enclosure,
                ],
            ];
            $this->fileIterator = $this->fileIteratorFactory->create($filePath, array_merge($defaultOptions, $this->options));
            $this->fileIterator->rewind();
        }

        $this->fileIterator->next();

        if ($this->fileIterator->valid() && null !== $this->stepExecution) {
            $this->stepExecution->incrementSummaryInfo('item_position');
        }

        $data = $this->fileIterator->current();

        if (null === $data) {
            return null;
        }

        $headers = $this->fileIterator->getHeaders();

        $countHeaders = count($headers);
        $countData    = count($data);

        $this->checkColumnNumber($countHeaders, $countData, $data, $filePath);

        if ($countHeaders > $countData) {
            $missingValuesCount = $countHeaders - $countData;
            $missingValues      = array_fill(0, $missingValuesCount, '');
            $data               = array_merge($data, $missingValues);
        }

        $item = array_combine($this->fileIterator->getHeaders(), $data);

        try {
            $item = $this->converter->convert($item, $this->getArrayConverterOptions());
        } catch (DataArrayConversionException $e) {
            $this->skipItemFromConversionException($item, $e);
        }

        return $item;
    }

    /**
     * @return array
     */
    protected function getArrayConverterOptions()
    {
        $jobParameters = $this->stepExecution->getJobParameters();

        return [
            'mapping'           => [
                $jobParameters->get('familyColumn')     => 'family',
                $jobParameters->get('categoriesColumn') => 'categories',
                $jobParameters->get('groupsColumn')     => 'groups'
            ],
            'with_associations' => false,

            'decimal_separator' => $jobParameters->get('decimalSeparator'),
            'date_format'       => $jobParameters->get('dateFormat'),
            'default_locale'    => $jobParameters->get('localeDefault')
        ];
    }
}
