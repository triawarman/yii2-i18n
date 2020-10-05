<?php
/**
 * @author Triawarman <3awarman@gmail.com>
 * @license MIT
 */

namespace triawarman\i18n;

/**
 * JsonMessageSource is modified version of `yii\i18n\PhpMessageSource` that represents a message source that stores translated messages in PHP scripts.
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
 * You may use [[fileMap]] to customize the association between category names and the file names.
 *
 */
class JsonMessageSource extends \yii\i18n\PhpMessageSource {
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
     * Returns message file path for the specified language and category.
     *
     * @param string $category the message category
     * @param string $language the target language
     * @return string path to message file
     */
    protected function getMessageFilePath($category, $language)
    {
        $messageFile = \yii::getAlias($this->basePath) . "/$language/";
        if (isset($this->fileMap[$category])) {
            $messageFile .= $this->fileMap[$category];
        } else {
            $messageFile .= str_replace('\\', '/', $category) . '.json';
        }
        
        return $messageFile;
    }
    
    /*
     * {@inheritdoc}
     */
    protected function loadMessagesFromFile($messageFile) {
        if (is_file($messageFile)) {
            /*
            $messages = include $messageFile;
            if (!is_array($messages)) {
                $messages = [];
            }
            */
            $messages = file_get_contents($messageFile);
            if($messages === false)
                $messages = [];
            else
                $messages = json_decode ($messages, true);

            return $messages;
        }

        return null;
    }
}
