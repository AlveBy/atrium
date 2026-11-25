<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\Changelog;
use Atrium\Ci\CiException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChangelogTest extends TestCase
{
    private const CHANGELOG = <<<'MD'
        # Changelog

        Intro.

        ## [Unreleased]

        The changes since the last release are in changelog/.

        ## [0.2.0] - 2026-11-24

        ### Added
        - Old thing.

        [Unreleased]: https://github.com/AlveBy/atrium/compare/v0.2.0...HEAD
        [0.2.0]: https://github.com/AlveBy/atrium/compare/v0.1.0...v0.2.0

        MD;

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/atrium-changelog-'.bin2hex(random_bytes(4));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    public function test_the_repository_itself_has_no_problems(): void
    {
        $this->assertSame([], Changelog::problems(dirname(__DIR__, 3)));
    }

    public function test_entries_come_by_section_in_order_and_by_file_name_trimmed(): void
    {
        $this->write('changelog/fixed/b.md', "- Fixed b.\n");
        $this->write('changelog/added/zeta.md', "\n- Added zeta.\n  More of zeta.\n\n");
        $this->write('changelog/added/alpha.md', "- Added alpha.\n- And a second item.\n");

        $this->assertSame([
            'added' => ["- Added alpha.\n- And a second item.", "- Added zeta.\n  More of zeta."],
            'fixed' => ['- Fixed b.'],
        ], Changelog::entries($this->root));
    }

    public function test_entries_skip_what_is_not_a_note_of_a_section(): void
    {
        $this->write('changelog/README.md', '# Notes');
        $this->write('changelog/stray.md', '- Not in a section.');
        $this->write('changelog/misc/thing.md', '- Not a section.');
        $this->write('changelog/added/thing.txt', '- Not Markdown.');

        $this->assertSame([], Changelog::entries($this->root));
    }

    public function test_no_changelog_folder_has_no_entries_and_no_problems(): void
    {
        $this->assertSame([], Changelog::entries($this->root));
        $this->assertSame([], Changelog::problems($this->root));
    }

    public function test_good_notes_and_a_clean_unreleased_section_have_no_problems(): void
    {
        $this->write('CHANGELOG.md', self::CHANGELOG);
        $this->write('changelog/README.md', '# Notes');

        foreach (array_keys(Changelog::SECTIONS) as $section) {
            $this->write("changelog/{$section}/one.md", "  - A note of {$section}.\n");
        }

        $this->assertSame([], Changelog::problems($this->root));
    }

    public function test_each_wrong_note_is_a_problem_of_its_own(): void
    {
        $this->write('changelog/stray.md', '- Outside a section.');
        $this->write('changelog/misc/thing.md', '- In no section.');
        $this->write('changelog/added/notes.txt', '- Not Markdown.');
        $this->write('changelog/added/prose.md', 'Not a list item.');
        $this->write('changelog/fixed/empty.md', '');
        $this->write('changelog/fixed/good.md', '- Good.');

        $this->assertSame([
            'changelog/added/notes.txt: an entry is a Markdown file, name.md',
            'changelog/added/prose.md: an entry is a list item, it starts with "- "',
            'changelog/fixed/empty.md: an entry is a list item, it starts with "- "',
            'changelog/misc: no such section, use added, changed, deprecated, removed, fixed, security',
            'changelog/stray.md: an entry goes into a section folder, changelog/{section}/stray.md',
        ], Changelog::problems($this->root));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unreleasedEntries(): array
    {
        return [
            'a list item' => ["- A change.\n"],
            'a section heading' => ["### Added\n"],
            'a list item after the note' => ["The note.\n\n- A change.\n"],
        ];
    }

    #[DataProvider('unreleasedEntries')]
    public function test_entries_under_unreleased_are_a_problem(string $body): void
    {
        $this->write('CHANGELOG.md', str_replace("The changes since the last release are in changelog/.\n", $body, self::CHANGELOG));

        $this->assertSame(
            ['CHANGELOG.md: [Unreleased] takes no entries, put each into changelog/{section}/{name}.md'],
            Changelog::problems($this->root),
        );
    }

    public function test_entries_of_a_released_version_and_a_dash_inside_a_line_are_no_problem(): void
    {
        $this->write('CHANGELOG.md', str_replace(
            "The changes since the last release are in changelog/.\n",
            "The notes - one file each - are in changelog/.\n",
            self::CHANGELOG,
        ));

        $this->assertSame([], Changelog::problems($this->root));
    }

    public function test_release_writes_the_version_section_and_moves_the_links_on(): void
    {
        $released = Changelog::release(self::CHANGELOG, [
            'fixed' => ['- Fixed one.'],
            'added' => ['- Added one.', "- Added two.\n- Added three."],
        ], '0.3.0', '2026-12-01');

        $this->assertSame(<<<'MD'
            # Changelog

            Intro.

            ## [Unreleased]

            The changes since the last release are in changelog/.

            ## [0.3.0] - 2026-12-01

            ### Added
            - Added one.
            - Added two.
            - Added three.

            ### Fixed
            - Fixed one.

            ## [0.2.0] - 2026-11-24

            ### Added
            - Old thing.

            [Unreleased]: https://github.com/AlveBy/atrium/compare/v0.3.0...HEAD
            [0.3.0]: https://github.com/AlveBy/atrium/compare/v0.2.0...v0.3.0
            [0.2.0]: https://github.com/AlveBy/atrium/compare/v0.1.0...v0.2.0

            MD, $released);
    }

    public function test_release_prints_every_section_in_the_keep_a_changelog_order(): void
    {
        $entries = [];

        foreach (array_reverse(array_keys(Changelog::SECTIONS)) as $section) {
            $entries[$section] = ["- {$section}"];
        }

        $released = Changelog::release(self::CHANGELOG, $entries, '0.3.0', '2026-12-01');

        $this->assertStringContainsString(
            "## [0.3.0] - 2026-12-01\n\n### Added\n- added\n\n### Changed\n- changed\n\n### Deprecated\n- deprecated\n\n"
            ."### Removed\n- removed\n\n### Fixed\n- fixed\n\n### Security\n- security\n\n## [0.2.0]",
            $released,
        );
    }

    public function test_release_takes_a_pre_release_version(): void
    {
        $released = Changelog::release(self::CHANGELOG, ['added' => ['- One.']], '0.3.0-beta.1', '2026-12-01');

        $this->assertStringContainsString("## [0.3.0-beta.1] - 2026-12-01\n", $released);
        $this->assertStringContainsString("[Unreleased]: https://github.com/AlveBy/atrium/compare/v0.3.0-beta.1...HEAD\n", $released);
    }

    /**
     * @return array<string, array{string, array<string, list<string>>, string, string, string}>
     */
    public static function refusals(): array
    {
        $added = ['added' => ['- One.']];

        return [
            'no version' => [self::CHANGELOG, $added, '', '2026-12-01', "'' is not a version, such as 0.3.0"],
            'a version with a v' => [self::CHANGELOG, $added, 'v0.3.0', '2026-12-01', "'v0.3.0' is not a version, such as 0.3.0"],
            'a version of two numbers' => [self::CHANGELOG, $added, '0.3', '2026-12-01', "'0.3' is not a version, such as 0.3.0"],
            'a version with text after it' => [self::CHANGELOG, $added, '0.3.0 x', '2026-12-01', "'0.3.0 x' is not a version, such as 0.3.0"],
            'a date in another form' => [self::CHANGELOG, $added, '0.3.0', '01.12.2026', "'01.12.2026' is not a date, such as 2026-12-01"],
            'a date with text after it' => [self::CHANGELOG, $added, '0.3.0', '2026-12-01x', "'2026-12-01x' is not a date, such as 2026-12-01"],
            'nothing to release' => [self::CHANGELOG, [], '0.3.0', '2026-12-01', 'changelog/ has no entries to release'],
            'a version already released' => [self::CHANGELOG, $added, '0.2.0', '2026-12-01', 'CHANGELOG.md already has 0.2.0'],
            'no compare link' => [
                str_replace('[Unreleased]: https://github.com/AlveBy/atrium/compare/v0.2.0...HEAD', '', self::CHANGELOG),
                $added, '0.3.0', '2026-12-01', 'CHANGELOG.md has no [Unreleased] compare link',
            ],
            'a compare link to another repository' => [
                str_replace('AlveBy/atrium/compare/v0.2.0...HEAD', 'Other/repo/compare/v0.2.0...HEAD', self::CHANGELOG),
                $added, '0.3.0', '2026-12-01', 'CHANGELOG.md has no [Unreleased] compare link',
            ],
            'no unreleased section' => [
                str_replace("## [Unreleased]\n", '', self::CHANGELOG),
                $added, '0.3.0', '2026-12-01', 'CHANGELOG.md has no [Unreleased] section followed by a release',
            ],
            'no release after the unreleased section' => [
                str_replace("## [0.2.0] - 2026-11-24\n", '', self::CHANGELOG),
                $added, '0.3.0', '2026-12-01', 'CHANGELOG.md has no [Unreleased] section followed by a release',
            ],
        ];
    }

    /**
     * @param  array<string, list<string>>  $entries
     */
    #[DataProvider('refusals')]
    public function test_release_refuses(string $changelog, array $entries, string $version, string $date, string $message): void
    {
        $this->expectException(CiException::class);
        $this->expectExceptionMessage($message);

        Changelog::release($changelog, $entries, $version, $date);
    }

    private function write(string $path, string $content): void
    {
        if (! is_dir(dirname($this->root.'/'.$path))) {
            mkdir(dirname($this->root.'/'.$path), 0777, true);
        }

        file_put_contents($this->root.'/'.$path, $content);
    }
}
