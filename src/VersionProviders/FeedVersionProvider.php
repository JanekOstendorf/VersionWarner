<?php declare(strict_types=1);
/**
 * @author Janek Ostendorf <janek@ostendorf-vechta.de>
 */

namespace ozzyfant\VersionWarner\VersionProviders;

use ozzyfant\VersionWarner\CantFetchVersionException;
use ozzyfant\VersionWarner\HttpVersionProvider;
use SimplePie;

class FeedVersionProvider extends HttpVersionProvider
{

    protected $fetched = false;
    protected $latestVersionCache;
    protected $latestDownloadLinkCache;

    protected $feedUrl;

    /**
     * Queries the feed and retrieves the information
     * @throws CantFetchVersionException
     */
    protected function queryFeed() {

        // Retrieve feed using cURL
        $raw = self::queryHttp($this->feedUrl);

        $feed = new SimplePie();
        $feed->set_raw_data($raw);
        $feed->enable_exceptions();

        try {
            $feed->init();
        } catch(\Exception $e) {
            throw new CantFetchVersionException("Error retrieving feed.", 0, $e);
        }

        if ($feed->get_item_quantity() <= 0) {
            throw new CantFetchVersionException("Feed does not contain items.");
        }

        try {
            $latestItem = $feed->get_item(0);
        } catch(\Exception $e) {
            throw new CantFetchVersionException("Could not retrieve any feed items.", 0, $e);
        }

        $this->latestVersionCache = $latestItem->get_title();
        $this->latestDownloadLinkCache = $latestItem->get_permalink();

        $this->fetched = true;
    }

    /**
     * What is the latest version?
     * @throws CantFetchVersionException
     * @return string
     *
     */
    function getLatestVersion(): string
    {
        if (!$this->fetched) {
            $this->queryFeed();
        }

        return $this->latestVersionCache;
    }

    /**
     * Get the (download) link for the new version.
     * @throws CantFetchVersionException
     * @return string
     */
    function getDownloadLink(): string
    {
        if (!$this->fetched) {
            $this->queryFeed();
        }

        return $this->latestDownloadLinkCache;
    }

    /**
     * Set the arguments provided in the config file
     * @param array $arguments The arguments from the config file
     */
    function setArguments(array $arguments)
    {
        if (isset($arguments['feedUrl'])) {
            $this->feedUrl = $arguments['feedUrl'];
        }
    }

    /**
     * How often are we allowed to ask this provider?
     * @return int Seconds
     */
    function getMinimalCheckInterval(): int
    {
        return 300;
    }

}