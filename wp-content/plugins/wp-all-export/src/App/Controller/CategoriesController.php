<?php

namespace Wpae\App\Controller;

use Wpae\Controller\BaseController;
use Wpae\Http\JsonResponse;
use Wpae\Http\Request;

class CategoriesController extends BaseController
{
    private function getTaxonomyHierarchy($parent = 0)
    {
        $terms = get_categories(
            array(
                'taxonomy'     => 'product_cat',
                'parent' => $parent,
                'hide_empty' => false
            )
        );

        $children = array();

        foreach ($terms as $term) {

            $item = array(
                'id' => $term->term_id,
                'title' => $term->name,
                'children' => $this->getTaxonomyHierarchy($term->term_id)
            );
            $children[] = $item;
        }

        return $children;
    }

    public function indexAction(Request $request)
    {
        $categories = array(
            'id' => 0,
            'title' => 'Root',
            'children' => $this->getTaxonomyHierarchy(0)
        );

       return new JsonResponse($categories);
    }
}