<?php
namespace AppBundle\Service;

use AppBundle\Entity\ApiKey;
use AppBundle\Entity\AuthToken;
use Doctrine\ORM\EntityManager;
use \DateTime;
use JMS\Serializer\Serializer;
use JMS\SerializerBundle\JMSSerializerBundle;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiKeyHelperService
 * @package AppBundle\Service
 */
class ApiHelperService
{
    private $serializer;
    private $apiKeyLength;

    /**
     * Constructor
     */
    public function __construct(Serializer $serializer, $apiKeyLength)
    {
        $this->serializer = $serializer;
        $this->apiKeyLength = $apiKeyLength;
    }

    public function getJsonResponseFromData($data, $groupsArray = array("Default"), $setHeaders = true, $version = null) {

        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $context->setGroups($groupsArray);
        if (!empty($version)) {
            $context->setVersion($version);
        }

        $response = new Response($this->serializer->serialize($data, 'json', $context));

        if ($setHeaders == true) {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    /**
     * This function returns an empty string when the authToken is valid
     * @return bool
     */
    public function getBooleanParameter($parameters, $parameterName) {

        $stringValue = isset($parameters[$parameterName]) ? $parameters[$parameterName] : "";

        if (mb_strtolower($stringValue) == "false") {
            return false;
        } else if (mb_strtolower($stringValue) == "true") {
            return true;
        } else {
            return null;
        }
    }

    /**
     * This function returns ... TODO
     * @return bool
     */
    public function getDecimalParameter($parameters, $parameterName) {

        if (isset($parameters[$parameterName])) {
            $stringValue = $parameters[$parameterName];
            if ($stringValue == "") {
                return null;
            }

            return floatval($stringValue);

        } else {
            return null;
        }
    }

    private function updateLastUsageOfToken(EntityManager $em, AuthToken $authToken) {

        $authToken->setLastUsage(new DateTime());
        $em->persist($authToken);
        $em->flush();

    }

    /**
     * This functions returns an error string when a rescue worker tried to access the api when there
     * are no active emergencies in the database
     * @return bool
     */
    public function roleCheck($em, RoleHelperService $roleHelperService, $user) {

        $userHasOnlyRescueWorkerRole = $roleHelperService->getUserHasOnlyRescueWorkerRole($em, $user->getRoles());

        if ($userHasOnlyRescueWorkerRole) {
            if ($roleHelperService->getActiveEmergenciesExist($em) == false) {
                return "Access Denied because there is no active emergency";
            }
        }
    }

    /**
     * This function returns an empty string when the authToken is valid
     * @return bool
     */
    public function authCheck($em, $parameters, RoleHelperService $roleHelperService) {

        $message = "";

        $authTokenString = isset($parameters['auth_token']) ? $parameters['auth_token'] : "";
        $apiKeyString = isset($parameters['api_key']) ? $parameters['api_key'] : "";

        if (empty($authTokenString) || empty($apiKeyString)) {
            $message = "Authentication failed: api_key or auth_token missing.";
        } else {

            /** @var ApiKey $apiKeyFromDatabase */
            $apiKeyFromDatabase = $em->getRepository('AppBundle:ApiKey')->findOneBy(array('apiKey' => $apiKeyString));
            /** @var AuthToken $authTokenFromDatabase */
            $authTokenFromDatabase = $em->getRepository('AppBundle:AuthToken')->findOneBy(array('token' => $authTokenString));

            if ($apiKeyFromDatabase && $authTokenFromDatabase && ($authTokenFromDatabase->getApiKey() == $apiKeyFromDatabase)) {
                //Check if AuthToken is still valid
                $currentDateTime = new DateTime();

                //Maybe this could be useful later
                if (!($authTokenFromDatabase->getGenerated() <= $currentDateTime)) {
                    $message = "Authentication failed: auth_token is not yet valid.";
                }

                //Last Login was less than an hour ago
                if (!( $authTokenFromDatabase->getLastUsage()->add(new \DateInterval('PT1H')) >= new DateTime())) {
                    $message = "Authentication failed: auth_token has timed out.";
                }

                if (!($authTokenFromDatabase->getExceeds() >= $currentDateTime)) {
                    $message = "Authentication failed: auth_token is not valid anymore.";
                }

                //Check if RescueWorker is allowed to access
                $message = $this->roleCheck($em, $roleHelperService, $apiKeyFromDatabase->getUser());

                $this->updateLastUsageOfToken($em, $authTokenFromDatabase);

            } else {
                //Auth-Token does not exist
                $message = "Authentication failed";
            }
        }

        return $message;
    }

    /**
     * The functions returns authToken (32 Characters with small and large characters and numbers)
     * @return string
     */
    public static function generateAuthToken() {
        $characterSet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return self::generateRandomString($characterSet, 32);
    }

    /**
     * The functions returns API-Key (32 Characters with large characters and numbers)
     * @return string
     */
    public function generateApiKey() {
        $characterSet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return self::generateRandomString($characterSet, $this->apiKeyLength);
    }

    /**
     * The functions generated a password (the password does not contain characters that are hard to distinguish
     * @return string
     */
    public static function generatePassword($length = 8) {
        //Without 0 and O
        //Without l and I
        //Without 8 and B
        //To prevent problems with similar looking characters
        $characterSet = '12345679ACDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz-_';
        return self::generateRandomString($characterSet, $length);
    }


    /**
     * The functions returns an alphanumeric String with the given length
     * @param {int} $length
     * @return string
     */
    private static function generateRandomString($characterSet, $length = 32) {

        $charactersLength = strlen($characterSet);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characterSet[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
