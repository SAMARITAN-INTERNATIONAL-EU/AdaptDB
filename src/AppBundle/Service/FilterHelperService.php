<?php
namespace AppBundle\Service;

/**
 * Class FilterHelperService
 * @package AppBundle\Service
 */
class FilterHelperService
{

    /**
     * The function adds a form-field to the filterArray (that is send to the database)
     * @param {mixed} $filterForm
     * @param {array} $filterArray
     * @param {string} $formFieldName
     * @param {string} $filterArrayPropertyName
     */
    public static function addFilterArrayPropertyFromFilterForm($filterForm, &$filterArray, $formFieldName, $filterArrayPropertyName) {

        if ($filterForm->has($formFieldName)) {
            $fieldData = $filterForm->get($formFieldName)->getData();
            if (is_string($fieldData) == true) {

                $query = trim($fieldData);
                if ($query != "") {
                    $filterArray[$filterArrayPropertyName] = $query;
                }
            } else {
                if ($fieldData != null) {
                    $filterArray[$filterArrayPropertyName] = $fieldData;
                }
            }
        }
    }
}
