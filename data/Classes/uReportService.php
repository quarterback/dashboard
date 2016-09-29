<?php
/**
 * @copyright 2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 */
namespace Site\Classes;

use Application\Models\ServiceInterface;
use Application\Models\ServiceDateValue;
use Blossom\Classes\Url;

class uReportService extends ServiceInterface
{
    const UREPORT_DATETIME_FORMAT = 'c';

    /**
     * Returns a list of all the methods and their parameters
     *
     * @return array
     */
    public function getMethods()
    {
        return [
            'onTimePercentage' => [
                'parameters' => ['category_id'=>'', 'numDays'=>'']
            ]
        ];
    }

    public function onTimePercentage(array $params)
    {
        if (!$params[parent::EFFECTIVE_DATE]) {
             $params[parent::EFFECTIVE_DATE] = new \DateTime();
        }
        $params[parent::EFFECTIVE_DATE] = $params[parent::EFFECTIVE_DATE]->format(self::UREPORT_DATETIME_FORMAT);

        $url = new Url($this->base_url.'/onTimePercentage');
        $url->parameters = $params;
        $url->format     = 'json';
        $url = $url->__toString();

        $response = Url::get($url);
        if ($response) {
            $json = json_decode($response);
            return new ServiceDateValue(
                         (int)$json->onTime,
                new \DateTime($json->effectiveDate)
            );
        }
    }
}