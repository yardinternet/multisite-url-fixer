<?php

declare(strict_types=1);

namespace Yard\Bedrock;

/**
 * Class URLFixer
 *
 * @package Yard\Bedrock
 *
 * @author Roots
 *
 * @link https://roots.io/
 */
class URLFixer
{
	/**
	 * Add filters to verify / fix URLs.
	 */
	public function addFilters(): void
	{
		add_filter('option_home', [$this, 'fixHomeURL']);
		add_filter('option_siteurl', [$this, 'fixSiteURL']);
		add_filter('network_site_url', [$this, 'fixNetworkSiteURL'], 10, 2);
		add_action('wp_update_site', [$this, 'syncSiteurlOnDomainChange'], 10, 2);
	}

	/**
	 * Ensure that home URL does not contain the /wp subdirectory.
	 */
	public function fixHomeURL(string $value): string
	{
		if (substr($value, -3) === '/wp') {
			$value = substr($value, 0, -3);
		}

		return $value;
	}

	/**
	 * Ensure that site URL contains the /wp subdirectory.
	 */
	public function fixSiteURL(string $url): string
	{
		if (substr($url, -3) !== '/wp' && (is_main_site() || is_subdomain_install())) {
			$url .= '/wp';
		}

		return $url;
	}

	public function syncSiteurlOnDomainChange(\WP_Site $newSite, \WP_Site $oldSite): void
	{
		if ($newSite->domain === $oldSite->domain && $newSite->path === $oldSite->path) {
			return;
		}

		$home = get_blog_option((int) $newSite->id, 'home');
		$scheme = parse_url((string) $home, PHP_URL_SCHEME) ?: 'https';
		$newSiteurl = rtrim($scheme . '://' . $newSite->domain . $newSite->path, '/') . '/wp';

		update_blog_option((int) $newSite->id, 'siteurl', $newSiteurl);
	}

	/**
	 * Ensure that the network site URL contains the /wp subdirectory.
	 */
	public function fixNetworkSiteURL(string $url, string $path): string
	{
		$path = ltrim($path, '/');
		$url = substr($url, 0, strlen($url) - strlen($path));

		if (substr($url, -3) !== 'wp/') {
			$url .= 'wp/';
		}

		return $url . $path;
	}
}
