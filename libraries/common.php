<?php
/**
 * Common functions for quiz topic management and validation.
 */


/**
 * Normalize a quiz topic slug
 */
function normalizeQuizTopicSlug($topic) : string
{
	$topic = strtolower(trim((string)$topic));
	return preg_replace('/[^a-z0-9_-]+/', '-', $topic);
}

/**
 * Get the list of allowed quiz topics based on the configuration and available folders.
 */
function getConfiguredQuizWhitelist(): array
{
	global $AVAILABLE_QUIZZES;

	if (!is_array($AVAILABLE_QUIZZES)) {
		return [];
	}

	$normalized = [];
	foreach ($AVAILABLE_QUIZZES as $topic) {
		$slug = trim(normalizeQuizTopicSlug($topic), '-');
		if ($slug !== '') {
			$normalized[] = $slug;
		}
	}

	return array_values(array_unique($normalized));
}

/**
 * Get the list of available quiz topics by scanning the topics directory and applying the whitelist filter.
 */
function getAvailableQuizTopics($topicsDir = './topics'): array
{
	$files = glob(rtrim($topicsDir, '/') . '/*', GLOB_ONLYDIR);
	$foundTopics = [];

	foreach ($files as $file) {
		if (!is_dir($file)) {
			continue;
		}
		$slug = basename($file);
		if ($slug !== '') {
			$foundTopics[] = $slug;
		}
	}

	sort($foundTopics);

	$whitelist = getConfiguredQuizWhitelist();
	if (count($whitelist) === 0) {
		return $foundTopics;
	}

	$allowed = [];
	foreach ($whitelist as $slug) {
		if (in_array($slug, $foundTopics, true)) {
			$allowed[] = $slug;
		}
	}

	return $allowed;
}

/**
 * Check if a given quiz topic is allowed based on the available topics and whitelist.
 */
function isQuizTopicAllowed($topic, $topicsDir = './topics'): bool
{
	$slug = trim(normalizeQuizTopicSlug($topic), '-');
	if ($slug === '') {
		return false;
	}

	$available = getAvailableQuizTopics($topicsDir);
	return in_array($slug, $available, true);
}