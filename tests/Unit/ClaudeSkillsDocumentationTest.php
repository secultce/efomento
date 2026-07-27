<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates the Claude Code configuration files added/changed in this PR:
 * - .claude/REVIEW.md
 * - .claude/skills/nova-tab/SKILL.md (+ checklist-backend.md)
 * - .claude/skills/novo-componente/SKILL.md
 * - .claude/skills/novo-composable/SKILL.md
 * - .gitignore (removal of the `.claude/skills/` and `.claude/settings.local.json` entries)
 *
 * These are plain-text/markdown assets consumed by Claude Code, not application
 * code, so the tests here are structural: they assert the files exist, that the
 * YAML frontmatter of each skill is well-formed and internally consistent, and
 * that the documented content matches what the PR actually introduced.
 */
class ClaudeSkillsDocumentationTest extends TestCase
{
    private const SKILL_DIRS = ['nova-tab', 'novo-componente', 'novo-composable'];

    private function claudeDir(): string
    {
        return dirname(__DIR__, 2).'/.claude';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function skillPath(string $skillDir): string
    {
        return $this->claudeDir()."/skills/{$skillDir}/SKILL.md";
    }

    /**
     * Splits a SKILL.md file into its YAML frontmatter block and markdown body.
     *
     * @return array{frontmatter: string, body: string}
     */
    private function splitFrontmatter(string $content): array
    {
        $this->assertMatchesRegularExpression(
            '/^---\r?\n(.*?)\r?\n---\r?\n(.*)$/s',
            $content,
            'File does not start with a well-formed --- frontmatter block.'
        );

        preg_match('/^---\r?\n(.*?)\r?\n---\r?\n(.*)$/s', $content, $matches);

        return ['frontmatter' => $matches[1], 'body' => $matches[2]];
    }

    /**
     * Extracts a single `key: value` field from a YAML frontmatter block.
     */
    private function frontmatterField(string $frontmatter, string $field): ?string
    {
        if (! preg_match('/^'.preg_quote($field, '/').':\s*(.+)$/m', $frontmatter, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    // -----------------------------------------------------------------
    // .claude/REVIEW.md
    // -----------------------------------------------------------------

    public function test_review_md_exists_and_is_not_empty(): void
    {
        $path = $this->claudeDir().'/REVIEW.md';

        $this->assertFileExists($path);
        $this->assertNotSame('', trim(file_get_contents($path)));
    }

    public function test_review_md_contains_all_expected_sections(): void
    {
        $content = file_get_contents($this->claudeDir().'/REVIEW.md');

        foreach ([
            '# Critérios de Code Review — e-Fomento',
            '## Como aplicar',
            '## Clean Code',
            '## SOLID / Arquitetura',
            '## Arquitetura do projeto (e-Fomento específico)',
            '## Boas práticas Laravel',
            '## Boas práticas JavaScript / Vue 3',
        ] as $section) {
            $this->assertStringContainsString($section, $content, "REVIEW.md is missing section: {$section}");
        }
    }

    public function test_review_md_references_concrete_project_pattern(): void
    {
        $content = file_get_contents($this->claudeDir().'/REVIEW.md');

        // The document explicitly grounds SRP guidance in a real, existing
        // method so reviewers have a concrete reference instead of an
        // abstract principle.
        $this->assertStringContainsString('ProjectStageService::ensureIsPrincipalSupervisor', $content);
    }

    public function test_review_md_forbids_unnecessary_new_abstractions(): void
    {
        $content = file_get_contents($this->claudeDir().'/REVIEW.md');

        $this->assertStringContainsString('over-engineering', $content);
        $this->assertStringContainsString('Repository pattern', $content);
    }

    // -----------------------------------------------------------------
    // Skill frontmatter (nova-tab, novo-componente, novo-composable)
    // -----------------------------------------------------------------

    public function test_all_expected_skill_files_exist(): void
    {
        foreach (self::SKILL_DIRS as $skillDir) {
            $this->assertFileExists($this->skillPath($skillDir), "Missing SKILL.md for '{$skillDir}'.");
        }
    }

    public function test_nova_tab_skill_frontmatter_is_valid(): void
    {
        $this->assertSkillFrontmatterIsValid('nova-tab');
    }

    public function test_novo_componente_skill_frontmatter_is_valid(): void
    {
        $this->assertSkillFrontmatterIsValid('novo-componente');
    }

    public function test_novo_composable_skill_frontmatter_is_valid(): void
    {
        $this->assertSkillFrontmatterIsValid('novo-composable');
    }

    private function assertSkillFrontmatterIsValid(string $skillDir): void
    {
        $content = file_get_contents($this->skillPath($skillDir));
        ['frontmatter' => $frontmatter, 'body' => $body] = $this->splitFrontmatter($content);

        $name = $this->frontmatterField($frontmatter, 'name');
        $description = $this->frontmatterField($frontmatter, 'description');

        $this->assertNotNull($name, "Skill '{$skillDir}' frontmatter is missing a 'name' field.");
        $this->assertNotNull($description, "Skill '{$skillDir}' frontmatter is missing a 'description' field.");

        // The skill's `name` must match its directory so Claude Code can
        // resolve it; a mismatch here is a silent, hard-to-notice bug.
        $this->assertSame($skillDir, $name);

        $this->assertNotSame('', $description);
        $this->assertGreaterThan(20, strlen($description), 'Skill description should be a meaningful sentence, not a stub.');

        $this->assertNotSame('', trim($body), "Skill '{$skillDir}' has no content after its frontmatter.");
    }

    public function test_skill_descriptions_document_trigger_phrases(): void
    {
        // Each skill's description should tell Claude Code *when* to use it
        // (the "Use quando..." convention used consistently across skills).
        foreach (self::SKILL_DIRS as $skillDir) {
            $content = file_get_contents($this->skillPath($skillDir));
            ['frontmatter' => $frontmatter] = $this->splitFrontmatter($content);
            $description = $this->frontmatterField($frontmatter, 'description');

            $this->assertStringContainsString('Use quando', $description, "Skill '{$skillDir}' description should document trigger phrases.");
        }
    }

    public function test_skill_names_are_unique(): void
    {
        $names = array_map(function (string $skillDir) {
            $content = file_get_contents($this->skillPath($skillDir));
            ['frontmatter' => $frontmatter] = $this->splitFrontmatter($content);

            return $this->frontmatterField($frontmatter, 'name');
        }, self::SKILL_DIRS);

        $this->assertCount(count($names), array_unique($names), 'Skill names must be unique across .claude/skills/.');
    }

    public function test_each_skill_body_has_a_top_level_heading_and_checklist(): void
    {
        foreach (self::SKILL_DIRS as $skillDir) {
            $content = file_get_contents($this->skillPath($skillDir));
            ['body' => $body] = $this->splitFrontmatter($content);

            $this->assertMatchesRegularExpression(
                '/^#\s+\S.*$/m',
                $body,
                "Skill '{$skillDir}' body is missing a top-level markdown heading."
            );

            $this->assertStringContainsString(
                'Checklist de conformidade',
                $content,
                "Skill '{$skillDir}' is missing its conformance checklist section."
            );
        }
    }

    // -----------------------------------------------------------------
    // nova-tab specific content
    // -----------------------------------------------------------------

    public function test_nova_tab_skill_warns_against_silently_generating_backend_code(): void
    {
        $content = file_get_contents($this->skillPath('nova-tab'));

        $this->assertStringContainsString('NUNCA gere model/migration/rotas silenciosamente', $content);
        $this->assertStringContainsString('checklist-backend.md', $content);
    }

    public function test_nova_tab_skill_forbids_v_data_table(): void
    {
        $content = file_get_contents($this->skillPath('nova-tab'));

        $this->assertStringContainsString('**Nunca** `v-data-table`', $content);
        $this->assertStringContainsString('ListDataTable', $content);
    }

    public function test_checklist_backend_md_exists_and_is_not_empty(): void
    {
        $path = $this->claudeDir().'/skills/nova-tab/checklist-backend.md';

        $this->assertFileExists($path);
        $this->assertNotSame('', trim(file_get_contents($path)));
    }

    public function test_checklist_backend_md_lists_all_expected_steps_in_order(): void
    {
        $content = file_get_contents($this->claudeDir().'/skills/nova-tab/checklist-backend.md');

        $steps = [
            'Migration',
            'Model',
            'Relação no Project',
            'Eager load',
            'Controller da etapa',
            'Form Request',
            'Rotas',
            'Enums',
        ];

        $lastPosition = -1;
        foreach ($steps as $step) {
            $position = strpos($content, $step);
            $this->assertNotFalse($position, "checklist-backend.md is missing step: {$step}");
            $this->assertGreaterThan($lastPosition, $position, "Step '{$step}' is out of the expected order.");
            $lastPosition = $position;
        }
    }

    public function test_checklist_backend_md_references_existing_formalization_files(): void
    {
        $content = file_get_contents($this->claudeDir().'/skills/nova-tab/checklist-backend.md');

        foreach ([
            'app/Models/Formalization.php',
            'app/Http/Controllers/FormalizationController.php',
            'app/Models/Project.php',
            'app/Http/Controllers/ProjectController.php',
        ] as $reference) {
            $this->assertStringContainsString($reference, $content, "checklist-backend.md should reference '{$reference}'.");
        }
    }

    public function test_checklist_backend_md_final_step_runs_migrations_and_tests(): void
    {
        $content = file_get_contents($this->claudeDir().'/skills/nova-tab/checklist-backend.md');

        $this->assertStringContainsString('php artisan migrate', $content);
        $this->assertStringContainsString('php artisan test', $content);
    }

    // -----------------------------------------------------------------
    // novo-componente specific content
    // -----------------------------------------------------------------

    public function test_novo_componente_skill_lists_existing_reusable_components(): void
    {
        $content = file_get_contents($this->skillPath('novo-componente'));

        foreach (['FormField', 'TextField', 'PrimaryButton', 'SplitScreenTab', 'ListDataTable'] as $component) {
            $this->assertStringContainsString($component, $content, "novo-componente skill should reference existing component '{$component}'.");
        }
    }

    public function test_novo_componente_skill_forbids_backend_calls_from_components(): void
    {
        $content = file_get_contents($this->skillPath('novo-componente'));

        $this->assertStringContainsString('Sem chamadas a backend dentro do componente reutilizável', $content);
    }

    // -----------------------------------------------------------------
    // novo-composable specific content
    // -----------------------------------------------------------------

    public function test_novo_composable_skill_lists_existing_composables(): void
    {
        $content = file_get_contents($this->skillPath('novo-composable'));

        foreach (['useAuth', 'useDate', 'useSnackbar', 'useAlert', 'useFormHelper', 'useObjectPath', 'useEnums', 'useMask'] as $composable) {
            $this->assertStringContainsString($composable, $content, "novo-composable skill should reference existing composable '{$composable}'.");
        }
    }

    public function test_novo_composable_skill_documents_naming_convention(): void
    {
        $content = file_get_contents($this->skillPath('novo-composable'));

        $this->assertStringContainsString('export function use', $content);
        $this->assertStringContainsString('resources/js/Composables/', $content);
    }

    // -----------------------------------------------------------------
    // .gitignore
    // -----------------------------------------------------------------

    private function gitignoreLines(): array
    {
        $content = file_get_contents($this->repoRoot().'/.gitignore');

        return array_map('rtrim', explode("\n", $content));
    }

    public function test_gitignore_no_longer_excludes_claude_skills_directory(): void
    {
        $this->assertNotContains('.claude/skills/', $this->gitignoreLines());
    }

    public function test_gitignore_no_longer_excludes_claude_settings_local_json(): void
    {
        $this->assertNotContains('.claude/settings.local.json', $this->gitignoreLines());
    }

    public function test_gitignore_has_no_remaining_claude_specific_entries(): void
    {
        $claudeEntries = array_filter($this->gitignoreLines(), fn (string $line) => str_contains($line, '.claude'));

        $this->assertSame([], array_values($claudeEntries), 'No .claude-specific entries should remain in .gitignore.');
    }

    public function test_gitignore_still_contains_preexisting_unrelated_entries(): void
    {
        $lines = $this->gitignoreLines();

        foreach ([
            '*.log',
            '.env',
            '/vendor',
            '/node_modules',
            'Thumbs.db',
            'cypress.env.json',
            'coverage.xml',
            'docs/spreadsheet-sync.md',
        ] as $expectedEntry) {
            $this->assertContains($expectedEntry, $lines, "Unrelated .gitignore entry '{$expectedEntry}' should not have been removed.");
        }
    }
}