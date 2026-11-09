<?php

namespace Tests\Feature\Modules\Catalogue;

use App\Modules\Catalogue\Infrastructure\Persistence\Concerns\HasCompositePrimaryKey;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\AuthorTag;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\AuthorTagAssignment;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\CatalogueCollection;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\CatalogueMedia;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Category;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ComplianceRestriction;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Edition;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\EditorialSelection;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\EducationalLevel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\EffectConfirmation;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Language;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\MeasureScope;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Offer;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\PublicationWithdrawalInstruction;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Publisher;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\RestrictionLift;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Review;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ScopeDevelopment;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\SelectionWork;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\TargetResolution;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\TechnicalFormat;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\WithdrawalOrderLine;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\Work;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\WorkAuthor;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\WorkCategory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\WorkLanguage;
use App\Modules\IdentityAccess\Infrastructure\Persistence\Models\CatalogAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogueModelMappingTest extends TestCase
{
    public function test_models_cover_the_stored_columns_of_their_tables(): void
    {
        foreach ($this->models() as $class) {
            /** @var Model $model */
            $model = new $class;
            $table = $model->getTable();
            $columns = $this->columns($table);

            $this->assertNotEmpty($columns, "La table {$table} est absente de livreaura_test.");
            $this->assertFalse($model->usesTimestamps(), $table);
            $this->assertFalse($model->getIncrementing(), $table);

            $stored = [];
            $generated = [];

            foreach ($columns as $column) {
                if (str_contains(strtoupper((string) $column->EXTRA), 'GENERATED')) {
                    $generated[] = $column->COLUMN_NAME;
                } else {
                    $stored[] = $column->COLUMN_NAME;
                }
            }

            $fillable = $model->getFillable();

            $this->assertEqualsCanonicalizing($stored, $fillable, $table);
            $this->assertSame([], array_values(array_intersect($generated, $fillable)), $table);
            $this->assertSame($this->primaryKey($table), $this->modelPrimaryKey($model), $table);
        }
    }

    /**
     * @return list<class-string<Model>>
     */
    private function models(): array
    {
        return [
            Work::class,
            Edition::class,
            TechnicalFormat::class,
            Offer::class,
            CatalogAuthor::class,
            Publisher::class,
            Category::class,
            Language::class,
            EducationalLevel::class,
            CatalogueCollection::class,
            AuthorTag::class,
            WorkAuthor::class,
            WorkCategory::class,
            WorkLanguage::class,
            AuthorTagAssignment::class,
            EditorialSelection::class,
            SelectionWork::class,
            CatalogueMedia::class,
            Review::class,
            ComplianceRestriction::class,
            RestrictionLift::class,
            WithdrawalOrderLine::class,
            TargetResolution::class,
            MeasureScope::class,
            PublicationWithdrawalInstruction::class,
            EffectConfirmation::class,
            ScopeDevelopment::class,
        ];
    }

    /**
     * @return list<object{COLUMN_NAME: string, EXTRA: string}>
     */
    private function columns(string $table): array
    {
        return DB::select(
            'SELECT COLUMN_NAME, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table],
        );
    }

    /**
     * @return list<string>
     */
    private function primaryKey(string $table): array
    {
        return array_map(
            static fn (object $column): string => $column->COLUMN_NAME,
            DB::select(
                'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? ORDER BY ORDINAL_POSITION',
                [$table, 'PRIMARY'],
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function modelPrimaryKey(Model $model): array
    {
        if (in_array(HasCompositePrimaryKey::class, class_uses_recursive($model), true)) {
            return $model->compositePrimaryKey();
        }

        return [$model->getKeyName()];
    }
}
