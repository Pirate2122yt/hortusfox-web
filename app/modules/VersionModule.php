<?php

/**
 * Class VersionModule
 *
 * Used to check for new versions. Reads the version straight out of
 * version.php on the GitHub repo configured in APP_GITHUB_URL (the
 * default branch), rather than the official HortusFox update service -
 * so a fork whose own version numbering has diverged from upstream's
 * only ever gets nagged about updates that exist in ITS OWN repo, not
 * upstream's release schedule. Set APP_GITHUB_URL to your own fork's
 * URL for this to compare against the right thing.
 */
class VersionModule {
    const CACHED_VERSION_TIME = 86400;

    /**
     * @return string
     */
    public static function getVersion()
    {
        try {
            $githubUrl = env('APP_GITHUB_URL');

            if ((!is_string($githubUrl)) || (!(strlen($githubUrl) > 0))) {
                throw new \Exception('No GitHub URL defined.');
            }

            $repoPath = trim((string)parse_url($githubUrl, PHP_URL_PATH), '/');

            if (substr($repoPath, -4) === '.git') {
                $repoPath = substr($repoPath, 0, -4);
            }

            if (strlen($repoPath) === 0) {
                throw new \Exception('Could not determine the repository from APP_GITHUB_URL.');
            }

            // Try both common default branch names rather than assuming
            // one - whichever responds first wins.
            foreach (['main', 'master'] as $branch) {
                $ch = curl_init('https://raw.githubusercontent.com/' . $repoPath . '/' . $branch . '/app/config/version.php');

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_errno($ch);

                curl_close($ch);

                if (($curlError === 0) && ($httpCode === 200) && (is_string($response)) && (strlen($response) > 0)) {
                    if (preg_match('/return\s*\'([0-9.]+)\'/', $response, $matches)) {
                        return $matches[1];
                    }
                }
            }

            throw new \Exception('Could not read version.php from ' . $repoPath . '.');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public static function getCachedVersion()
    {
        try {
            return CacheModel::remember('software_version', self::CACHED_VERSION_TIME, function() {
                return VersionModule::getVersion();
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
