<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Database\Entities\User;
use Flute\Core\Events\SearchEvent;
use Flute\Core\Http\Controllers\SearchController;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Search\SearchHandler;
use Flute\Core\Search\SearchResult;

use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SearchServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            SearchHandler::class => \DI\create(),
            "search" => \DI\get(SearchHandler::class),
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        if( is_installed() )
        {
            $container->get(RouteDispatcher::class)->post('api/search/{value}', [SearchController::class, 'search']);
            $container->get(EventDispatcher::class)->addListener(SearchEvent::NAME, [$this, 'searchById']);
        }
    }

    public function searchById(SearchEvent $searchEvent) : void
    {
        $value = $searchEvent->getValue();

        $foundUsers = rep(User::class)->select()->where('name', 'like', "%$value%")->fetchAll();
        
        if( sizeof($foundUsers) > 0 ) {
            foreach( $foundUsers as $foundUser ) {
                
                if( $searchEvent->isExists($foundUser->id, 'user') )
                    continue;

                $searchResult = new SearchResult;
                $searchResult->setType('name');
                $searchResult->setId($foundUser->id);
                $searchResult->setImage(url($foundUser->avatar));
                $searchResult->setUrl(url("profile/{$foundUser->id}"));
                $searchResult->setTitle($foundUser->name);
                $searchResult->setDescription(__('def.profile'));

                $searchEvent->add($searchResult);
            }
        }
    }
}