<?php

namespace Flute\Admin\Packages\Search\Controllers;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select\Repository;
use Flute\Admin\Packages\Search\SelectRegistry;
use Flute\Core\Support\FluteRequest;

use function mb_strlen;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function trim;

class AdminSelectController
{
    protected SelectRegistry $registry;

    protected ORMInterface $orm;

    public function __construct(SelectRegistry $registry, ORMInterface $orm)
    {
        $this->registry = $registry;
        $this->orm = $orm;
    }

    public function search(FluteRequest $request): JsonResponse
    {
        $alias = $request->input('entity');
        $query = trim($request->input('query', ''));

        $config = $this->registry->getEntityConfig($alias);
        if (!$config) {
            return response()->json(['error' => 'Unknown entity'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->registry->canUserAccessAlias($alias)) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $class = $config['class'];
        $displayField = $config['displayField'];
        $valueField = $config['valueField'] ?? 'id';
        $searchFields = $config['searchFields'] ?? [];
        $limit = $config['limit'] ?? 20;

        // Minimum query length = 2
        if (mb_strlen($query) < 2 && $query !== '') {
            return response()->json([]);
        }

        /** @var Repository $repository */
        $repository = $this->orm->getRepository($class);
        $select = $repository->select();

        if (!empty($config['scope']) && is_callable($config['scope'])) {
            ($config['scope'])($select);
        }

        $searchFields = $config['searchFields'] ?? [];
        $select->where(static function ($q) use ($searchFields, $query) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });

        $select->limit($limit);

        $displayField ??= 'name';
        if ($displayField) {
            $select->orderBy($displayField, 'ASC');
        }

        $items = $select->fetchAll();

        $results = [];

        foreach ($items as $item) {
            $results[] = [
                'value' => $item->{$valueField},
                'text' => $item->{$displayField},
            ];
        }

        return response()->json($results);
    }
}
