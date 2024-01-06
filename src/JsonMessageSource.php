<?php
/**
 * @author Triawarman <3awarman@gmail.com>
 * @license MIT
 */

namespace triawarman\i18n;

use Yii;

/**
 * 
 * Modified of \yii\i18n\PhpMessageSource.
 * 
 * JsonMessageSource represents a message source that stores translated messages in json file.
 * 
 * JsonMessageSource uses json arrays to keep message translations.
 * 
 * - Each json file contains one array which stores the message translations in one particular
 *   language and for a single message category;
 * - Each json file is saved as a file named as "[[basePath]]/LanguageID/CategoryName.json";
 * - Within each json file, the message translations are returned as an array like the following:
 *
 * ```json
 * {
 *     "original message 1" : "translated message 1",
 *     "original message 2" : "translated message 2"
 * }
 * ```
 * 
 * how to setup the i18n commponent
 * ```
 * 'translations' => [
 *      'app*' => [
 *          'class' => 'triawarman\i18n\JsonMessageSource',
 *          //'basePath' => '@app/messages',
 *          //'sourceLanguage' => 'en-US',
 *          'fileMap' => [
 *              'app' => 'app.json',
 *              'app/error' => 'error.json',
 *              'app/message' => ['message.json', '@aliasPath/{language}/file.json']
 *          ],
 *      ],
 * ]
 * ```
 *
 */
class JsonMessageSource extends \yii\i18n\MessageSource {
    /**
     * @var string the base path for all translated messages. Defaults to '@app/messages'.
     */
    public $basePath = '@app/messages';
    /**
     * @var array mapping between message categories and the corresponding message file paths.
     * The file paths are relative to [[basePath]]. For example,
     *
     * ```php
     * [
     *     'core' => 'core.json',
     *     'ext' => 'extensions.json',
     * ]
     * ```
     */
    public $fileMap;
    
    /**
     * 
     * @param string $language
     * @param string $file
     * 
     * return string path to file
     */
    private function mappingToAliasPath ($language, $file) {
        //'@aliasPath/{language}/file.json' || '@aliasPath/anotherPath/{language}/file.json'
        $paths = explode('/', $file); //['@aliasPath', 'anotherPath', '{language}', 'file.json']
        $recPaths = [];
        $tFile = $paths[(count($paths) - 1)];//'file.json'
        $paths = array_slice($paths, 0, (count($paths) - 2));//['@aliasPath', 'anotherPath']
        $count = count($paths);
        for ($index = 0; $index < $count; $index++) {//$count=2, $index=0,
            $file = \yii::getAlias(implode('/', $paths));
            if($file != false) {
                if(count($recPaths) > 1)
                    $file .= implode('/', $recPaths)."/$language/".$tFile;
                elseif(count($recPaths) == 1)
                    $file .= '/' . $recPaths . "/$language/".$tFile;
                else
                    $file .= "/$language/".$tFile;
                break;
            }

            $recPaths = array_merge([$paths[($count - ($index + 1))]], $recPaths); //['anotherPath']
            $paths = array_pop($paths);//['@aliasPath']
        }
        
        return $file;
    }
    
    /**
     * Returns message file path for the specified language and category.
     *
     * @param string $category the message category
     * @param string $language the target language
     * @return string path to message file
     */
    protected function getMessageFilePath($category, $language) {
        $language = (string) $language;
        if ($language !== '' && !preg_match('/^[a-z0-9_-]+$/i', $language))
            throw new InvalidArgumentException(sprintf('Invalid language code: "%s".', $language));
        
        $messageFile = null;
        $basePath = Yii::getAlias($this->basePath) . "/$language/";
        if (empty($this->fileMap[$category]))
            $messageFile = $basePath.str_replace('\\', '/', $category) . '.json';
        else {
            if(is_string($this->fileMap[$category])) {
                $file = $this->fileMap[$category];
                if(substr($file, 0, 1) != '@')
                    $messageFile = $basePath.$this->fileMap[$category];
                else
                    $messageFile = $this->mappingToAliasPath($language, $file);
            }
            elseif(is_array($this->fileMap[$category])) {
                foreach($this->fileMap[$category] as $file) {
                    if(substr($file, 0, 1) != '@')
                        $messageFile[] = $basePath.$file;
                    else
                        $messageFile[] = $this->mappingToAliasPath($language, $file);
                }
            }
                
        }

        return $messageFile;
    }
    
    /*
     * {@inheritdoc}
     */
    protected function loadMessagesFromFile($messageFile) {
        $messages = [];
        if ((is_string($messageFile) ? is_file($messageFile) : false) == true) {
            $messages = file_get_contents($messageFile);
            if($messages === false)
                $messages = [];
            else
                $messages = json_decode ($messages, true);
        }
        elseif(is_array($messageFile)) {
            $tempArr = [];
            $messageFile = array_reverse ($messageFile);
            foreach ($messageFile as $file) {
                if (is_file($file)) {
                    $tempArr = file_get_contents($file);
                    if($tempArr === false)
                        $tempArr = [];
                    else
                        $tempArr = json_decode ($tempArr, true);

                    $messages = array_merge($messages, $tempArr);
                }
            }
        }
        
        return $messages;
    }
    /**
     * The method is normally called by [[loadMessages]] to load the fallback messages for the language.
     * Method tries to load the $category messages for the $fallbackLanguage and adds them to the $messages array.
     *
     * @param string $category the message category
     * @param string $fallbackLanguage the target fallback language
     * @param array $messages the array of previously loaded translation messages.
     * The keys are original messages, and the values are the translated messages.
     * @param string $originalMessageFile the path to the file with messages. Used to log an error message
     * in case when no translations were found.
     * @return array the loaded messages. The keys are original messages, and the values are the translated messages.
     * @since 2.0.7
     */
    protected function loadFallbackMessages($category, $fallbackLanguage, $messages, $originalMessageFile)
    {
        $fallbackMessageFile = $this->getMessageFilePath($category, $fallbackLanguage);
        $fallbackMessages = $this->loadMessagesFromFile($fallbackMessageFile);

        if (
            $messages === null && $fallbackMessages === null
            && $fallbackLanguage !== $this->sourceLanguage
            && strpos($this->sourceLanguage, $fallbackLanguage) !== 0
        ) {
            Yii::error("The message file for category '$category' does not exist: $originalMessageFile "
                . "Fallback file does not exist as well: $fallbackMessageFile", __METHOD__);
        } elseif (empty($messages)) {
            return $fallbackMessages;
        } elseif (!empty($fallbackMessages)) {
            foreach ($fallbackMessages as $key => $value) {
                if (!empty($value) && empty($messages[$key])) {
                    $messages[$key] = $value;
                }
            }
        }

        return (array) $messages;
    }
    
    /**
     * Loads the message translation for the specified $language and $category.
     * If translation for specific locale code such as `en-US` isn't found it
     * tries more generic `en`. When both are present, the `en-US` messages will be merged
     * over `en`. See [[loadFallbackMessages]] for details.
     * If the $language is less specific than [[sourceLanguage]], the method will try to
     * load the messages for [[sourceLanguage]]. For example: [[sourceLanguage]] is `en-GB`,
     * $language is `en`. The method will load the messages for `en` and merge them over `en-GB`.
     *
     * @param string $category the message category
     * @param string $language the target language
     * @return array the loaded messages. The keys are original messages, and the values are the translated messages.
     * @see loadFallbackMessages
     * @see sourceLanguage
     */
    protected function loadMessages($category, $language) {
        $messageFile = $this->getMessageFilePath($category, $language);
        $messages = $this->loadMessagesFromFile($messageFile);

        $fallbackLanguage = substr((string)$language, 0, 2);
        $fallbackSourceLanguage = substr($this->sourceLanguage, 0, 2);

        if ($fallbackLanguage !== '' && $language !== $fallbackLanguage) {
            $messages = $this->loadFallbackMessages($category, $fallbackLanguage, $messages, $messageFile);
        } elseif ($fallbackSourceLanguage !== '' && $language === $fallbackSourceLanguage) {
            $messages = $this->loadFallbackMessages($category, $this->sourceLanguage, $messages, $messageFile);
        } elseif ($messages === null) {
            Yii::warning("The message file for category '$category' does not exist: $messageFile", __METHOD__);
        }

        return (array) $messages;
    }
}