<?php

declare(strict_types=1);

namespace App\Modules\Project\DTO;

use Illuminate\Http\Request;
use App\Modules\Area\DTO\CreateAreaDTO;
use App\Modules\Area\DTO\CreateLotDTO;

final class BulkCreateProjectDTO
{
    /**
     * @param CreateProjectDTO $project
     * @param CreateAreaDTO|null $area
     * @param CreateLotDTO[] $lots
     */
    public function __construct(
        public readonly CreateProjectDTO $project,
        public readonly ?CreateAreaDTO $area = null,
        public readonly array $lots = []
    ) {}

    public static function fromRequest(Request $request): self
    {
        $projectDto = CreateProjectDTO::fromRequest($request);

        $areaDto = null;
        if ($request->has('area') && is_array($request->input('area'))) {
            $areaDto = CreateAreaDTO::fromArray($request->input('area'));
        }

        $lotDtos = [];
        if ($request->has('lots') && is_array($request->input('lots'))) {
            foreach ($request->input('lots') as $lotData) {
                $lotDtos[] = CreateLotDTO::fromArray($lotData);
            }
        }

        return new self(
            project: $projectDto,
            area: $areaDto,
            lots: $lotDtos
        );
    }
}
