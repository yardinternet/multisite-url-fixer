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
		add_action('admin_init', [$this, 'validateDomainOnSiteInfoSave']);
		add_action('network_admin_notices', [$this, 'showDomainTakenNotice']);
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

	public function validateDomainOnSiteInfoSave(): void
	{
		global $pagenow;

		if (
			! is_network_admin()
			|| 'site-info.php' !== $pagenow
			|| ! isset($_REQUEST['action'], $_POST['blog']['url'])
			|| 'update-site' !== $_REQUEST['action']
		) {
			return;
		}

		$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
		if (! $id || is_main_site($id)) {
			return;
		}

		$site = get_site($id);
		if (! $site) {
			return;
		}

		check_admin_referer('edit-site');

		$scheme = parse_url(get_blog_option($id, 'siteurl'), PHP_URL_SCHEME) ?: 'https';
		$newUrl = wp_unslash((string) $_POST['blog']['url']);

		if (! parse_url($newUrl, PHP_URL_SCHEME)) {
			$newUrl = $scheme . '://' . $newUrl;
		}

		$parsed = parse_url($newUrl);
		$newDomain = $parsed['host'] ?? '';
		$newPath = trailingslashit('/' . trim($parsed['path'] ?? '/', '/'));

		if (! $newDomain) {
			return;
		}

		$taken = domain_exists($newDomain, $newPath, (int) $site->site_id);
		if ($taken && (int) $taken !== $id) {
			wp_safe_redirect(add_query_arg(['update' => 'domain_taken', 'id' => $id], network_admin_url('site-info.php')));
			exit();
		}
	}

	public function showDomainTakenNotice(): void
	{
		if (! isset($_GET['update']) || 'domain_taken' !== $_GET['update']) {
			return;
		}

		wp_admin_notice(__('A site with that domain already exists. The URL was not changed.'), [
			'type' => 'error',
			'dismissible' => true,
			'id' => 'message',
		]);
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
