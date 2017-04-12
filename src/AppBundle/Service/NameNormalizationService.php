<?php
namespace AppBundle\Service;

/**
 * Class NameNormalizationService
 * @package AppBundle\Service
 */
class NameNormalizationService
{

    /**
     * The function normalizes a name. Special characters are removed 
     * @return {string} The normalized name
     * @param {string} $nameString
     * @param {bool} $keepWildcards When $keepWildcards is true, the "*" character will not be removed - This allows the user to use wildcards in a search.
     */
    public function normalizeName($nameString, $keepWildcards = true)
    {
        $normalizedName = mb_strtoupper($nameString);

        //@TODO Maybe add other replacements for other special characters like â => a ?
        $normalizedName = str_replace("ß", "SS", $normalizedName);
        $normalizedName = str_replace("Ä", "AE", $normalizedName);
        $normalizedName = str_replace("Ü", "UE", $normalizedName);
        $normalizedName = str_replace("Ö", "OE", $normalizedName);

        if ($keepWildcards == true) {
            $normalizedName = mb_ereg_replace("[^A-Z\\*]", "", $normalizedName);
        } else {
            $normalizedName = mb_ereg_replace("[^A-Z]", "", $normalizedName);
        }

        return $normalizedName;
    }

    /**
     * This function is used to equalize the street names. 
     * @return {string} The beautified street name
     * @param {string} $streetName
     */
    public function normalizeStreetName($streetName, $keepWildcards = true)
    {
        $beautifiedStreetName = $this->normalizeName($streetName, $keepWildcards);
        
        $beautifiedStreetName = mb_strtoupper($beautifiedStreetName);

        $beautifiedStreetName = mb_ereg_replace("(STRASSE\b|STREET\b)$", "STR", $beautifiedStreetName);

        return $beautifiedStreetName;
    }
}
