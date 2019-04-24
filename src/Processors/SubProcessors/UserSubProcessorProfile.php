<?php
class UserSubProcessorProfile extends UserSubProcessor
{
    const URL_PROFILE = 0;
    
    public function getURLs($name)
    {
        return [
            self::URL_PROFILE => 'https://myanimelist.net/profile/' . $name
        ];
    }

    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_PROFILE];
        
        $dom = self::getDOM($document);
        
        $xpath = new DOMXPath($dom);
        
        if ($xpath->query('//div[@class = \'error404\']')->length >= 1) {
            throw new BadProcessorKeyException($context->key);
        }
        
        $name = Strings::removeSpaces(self::getNodeValue($xpath, '//h1//span'));
        $name = substr($name, 0, strpos($name, '\'s Profile'));
        $name = Strings::removeSpaces($name);
        
        if (empty($name)) {
            throw new BadProcessorDocumentException($document, 'Username missing');
        }
        
        $image = self::getNodeValue($xpath, '//div[contains(@class, \'user-image\')]//img', null, 'src');
        
        $joinDate = Strings::makeDate(self::getNodeValue($xpath, '//span[text() = \'Joined\']/following-sibling::span'));
        
        $anime = Strings::makeInteger(self::getNodeValue($xpath, '//div[contains(@class, \'anime\')]//span[text() = \'Total Entries\']/following-sibling::span'));
        
        $manga = Strings::makeInteger(self::getNodeValue($xpath, '//div[contains(@class, \'manga\')]//span[text() = \'Total Entries\']/following-sibling::span'));
        
        $animere = Strings::makeInteger(self::getNodeValue($xpath, '//div[contains(@class, \'anime\')]//span[text() = \'Rewatched\']/following-sibling::span'));
        
        $mangare = Strings::makeInteger(self::getNodeValue($xpath, '//div[contains(@class, \'manga\')]//span[text() = \'Reread\']/following-sibling::span'));
        
        $user = &$context->user;
        
        $user->name = $name;
        
        $user->picture_url = $image;
        
        $user->join_date = $joinDate;
        
        $user->anime_days_spent = $anime;
        
        $user->manga_days_spent = $manga;
        
        $user->anime_views = $animere;
        
        $user->manga_views = $mangare;
        
        $user->processed = date('Y-m-d H:i:s');
        
        R::store($user);
    }
}
