<?php
namespace Extensions\Bundle\ProductOptionsConnectorBundle\Reader;

use Akeneo\Pim\Enrichment\Component\Product\Connector\Reader\File\Csv\ProductReader as BaseReader;

/**
 * Class ProductReader
 *
 * @author                 Nicolas SOUFFLEUR, Akeneo Expert <contact@nicolas-souffleur.com>
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductReader extends BaseReader
{

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
