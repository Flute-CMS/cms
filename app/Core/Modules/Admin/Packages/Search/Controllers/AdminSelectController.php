<?php

namespace Flute\Admin\Packages\Search\Controllers;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select\Repository;
use Flute\Admin\Packages\Search\SelectRegistry;
use Flute\Core\Support\FluteRequest;

use function mb_strlen;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        $page = max(1, (int) $request->input('page', 1));

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
        $limit = $config['limit'] ?? 20;
        $extraFields = $config['extraFields'] ?? [];
        $optionView = $config['optionView'] ?? null;
        $itemView = $config['itemView'] ?? null;

        $requestExtraFields = $request->input('extraFields');
        if ($requestExtraFields) {
            try {
                $parsed = json_decode($requestExtraFields, true);
                if (is_array($parsed)) {
                    $extraFields = array_unique(array_merge($extraFields, $parsed));
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        $requestOptionView = $request->input('optionView');
        $requestItemView = $request->input('itemView');
        if ($requestOptionView && $this->isAllowedView($requestOptionView)) {
            $optionView = $requestOptionView;
        }
        if ($requestItemView && $this->isAllowedView($requestItemView)) {
            $itemView = $requestItemView;
        }

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
        if ($query !== '') {
            $escapedQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
            $select->where(static function ($q) use ($searchFields, $escapedQuery) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$escapedQuery}%");
                }
            });
        }

        $displayField ??= 'name';

        if ($query === '') {
            $preloadLimit = $config['preloadLimit'] ?? $limit;
            $select->limit($preloadLimit);
            $select->offset(($page - 1) * $preloadLimit);
            $select->orderBy('id', 'DESC');
        } else {
            $select->limit($limit);
            $select->offset(($page - 1) * $limit);
            if ($displayField) {
                $select->orderBy($displayField, 'ASC');
            }
        }

        $items = $select->fetchAll();

        $results = [];

        foreach ($items as $item) {
            $text = $item->{$displayField};
            $value = $item->{$valueField};

            $row = [
                'value' => $value,
                'text' => $text,
            ];

            foreach ($extraFields as $field) {
                if (isset($item->{$field})) {
                    $row[$field] = $item->{$field};
                }
            }

            try {
                if ($optionView) {
                    $row['optionHtml'] = view($optionView, [
                        'item' => $item,
                        'text' => $text,
                        'value' => $value,
                    ])->render();
                }

                if ($itemView) {
                    $row['itemHtml'] = view($itemView, [
                        'item' => $item,
                        'text' => $text,
                        'value' => $value,
                    ])->render();
                }
            } catch (Throwable $e) {
                // View rendering failed — return text-only option
            }

            $results[] = $row;
        }

        return response()->json($results);
    }

    /**
     * Only allow views from admin namespace to prevent arbitrary view rendering.
     */
    protected function isAllowedView(string $view): bool
    {
        return str_starts_with($view, 'admin::');
    }
}
