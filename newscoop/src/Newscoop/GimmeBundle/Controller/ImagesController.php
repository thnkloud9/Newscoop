<?php
/**
 * @package Newscoop\Gimme
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\GimmeBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\View as FOSView;
use Newscoop\Entity\LocalImage;
use Newscoop\Entity\User;
use Newscoop\GimmeBundle\Form\Type\ImageType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Images controller
 */
class ImagesController extends FOSRestController
{
    /**
     * Get all images
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404={
     *           "Returned when the images are not found"
     *         }
     *     }
     * )
     *
     * @Route("/images.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("GET")
     * @View(serializerGroups={"list"})
     *
     * @return array
     */
    public function getImagesAction(Request $request)
    {
        $em = $this->container->get('em');

        $images = $em->getRepository('Newscoop\Image\LocalImage')
            ->getImages();

        $paginator = $this->get('newscoop.paginator.paginator_service');
        $images = $paginator->paginate($images, array(
            'distinct' => false
        ));

        return $images;
    }

    /**
     * Search for images
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404={
     *           "Returned when the images are not found"
     *         }
     *     },
     *     parameters={
     *         {"name"="query", "dataType"="mixed", "required"=false, "description"="Image serach query"},
     *         {"name"="uploader", "dataType"="integer", "required"=false, "description"="Uploader id"}
     *     },
     * )
     *
     * @Route("/search/images.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("GET")
     * @View(serializerGroups={"list"})
     *
     * @return array
     */
    public function searchImagesAction(Request $request)
    {
        $imagesSerach = $this->container->get('image.search');
        $criteria = array();
        $count = null;

        if ($request->query->get('uploader', false)) {
            $criteria = array('user' => $request->query->get('uploader'));
        }

        $images = $imagesSerach->find($request->query->get('query', false), $criteria, null, null, $count, true);

        $paginator = $this->get('newscoop.paginator.paginator_service');
        $images = $paginator->paginate($images, array(
            'distinct' => false
        ));

        return $images;
    }

    /**
     * Get image
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404={
     *           "Returned when the image is not found",
     *         }
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Image id"}
     *     },
     *     output="\Newscoop\Image\LocalImage"
     * )
     *
     * @Route("/images/{number}.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("GET")
     * @View(serializerGroups={"details"})
     *
     * @return Form
     */
    public function getImageAction(Request $request, $number)
    {
        $em = $this->container->get('em');

        $image = $em->getRepository('Newscoop\Image\LocalImage')
            ->getImage($number)
            ->getOneOrNullResult();

        if (!$image) {
            throw new EntityNotFoundException('Result was not found.');
        }

        return $image;
    }

    /**
     * Get all images for specified article
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         404={
     *           "Returned when the images are not found",
     *         }
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Image id"},
     *         {"name"="language", "dataType"="string", "required"=true, "description"="Two letters code for article language"}
     *     }
     * )
     *
     * @Route("/articles/{number}/{language}/images.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("GET")
     * @View(serializerGroups={"list"})
     */
    public function getImagesForArticleAction($number, $language)
    {
        $em = $this->container->get('em');
        $paginatorService = $this->get('newscoop.paginator.paginator_service');
        $paginatorService->setUsedRouteParams(array('number' => $number, 'language' => $language));

        $article = $em->getRepository('Newscoop\Entity\Article')
            ->getArticle($number, $language)
            ->getOneOrNullResult();

        if (!$article) {
            throw new EntityNotFoundException('Article with number:"'.$number.'" and language: "'.$language.'" was not found.');
        }

        $articleImages = $em->getRepository('Newscoop\Image\ArticleImage')
            ->getArticleImages($number);

        $paginator = $this->get('newscoop.paginator.paginator_service');
        $articleImages = $paginator->paginate($articleImages);

        $images = array();
        foreach ($articleImages['items'] as $articleImage) {
            $image = $articleImage->getImage();
            $image->setArticleImageId($articleImage->getNumber());
            $images[] = $image;
        }

        $images = $paginator->paginate($images);

        if (array_key_exists('pagination', $articleImages)) {
            $images['pagination'] = $articleImages['pagination'];
        }

        return $images;
    }

    /**
     * Create new image
     *
     * Request:
     * Data should be send as multiparts formdata.
     *
     * Response:
     * Succesful response will contain "X-Location" header with path to new resource.
     *
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when image created succesfuly"
     *     },
     *     input="\Newscoop\GimmeBundle\Form\Type\ImageType"
     * )
     *
     * @Route("/images.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("POST")
     * @View()
     *
     * @return Form
     */
    public function createImageAction(Request $request)
    {
        return $this->processForm($request);
    }

    /**
     * Update image
     *
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when image updated succesfuly",
     *         404={
     *           "Returned when the images are not found",
     *         }
     *     },
     *     input="\Newscoop\GimmeBundle\Form\Type\ImageType",
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Image id"}
     *     }
     * )
     *
     * @Route("/images/{number}.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("POST|PATCH")
     * @View()
     *
     * @return Form
     */
    public function updateImageAction(Request $request, $number)
    {
        return $this->processForm($request, $number);
    }

    /**
     * Delete image
     *
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when image removed succesfuly",
     *         404={
     *           "Returned when the images are not found",
     *         }
     *     },
     *     parameters={
     *         {"name"="number", "dataType"="integer", "required"=true, "description"="Image id"}
     *     }
     * )
     *
     * @Route("/images/{number}.{_format}", defaults={"_format"="json"}, options={"expose"=true})
     * @Method("DELETE")
     * @View(statusCode=204)
     *
     * @return Form
     */
    public function deleteImageAction(Request $request, $number)
    {
        $imageService = $this->container->get('image');
        $em = $this->container->get('em');
        $image = $em->getRepository('Newscoop\Image\LocalImage')->findOneById($number);

        if (!$image) {
            throw new EntityNotFoundException('Result was not found.');
        }

        $imageService->remove($image);
    }

    /**
     * Process image form
     *
     * @param Request $request
     * @param integer $image
     *
     * @return Form
     */
    private function processForm($request, $image = null)
    {
        $em = $this->container->get('em');
        $imageService = $this->container->get('image');

        if (!$image) {
            $statusCode = 201;
        } else {
            $statusCode = 200;
            $image = $em->getRepository('Newscoop\Image\LocalImage')->findOneById($image);

            if (!$image) {
                throw new EntityNotFoundException('Result was not found.');
            }
        }

        $form = $this->createForm(new ImageType(), array(), array('image' => $image));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $file = $form['image']->getData();
            $attributes = $form->getData();
            $user = $this->getUser();

            if ($user) {
                $attributes['user'] = $user;
            }

            if ($file) {
                $image = $imageService->upload($file, $attributes, $image);
            } else {
                $imageService->fillImage($image, $attributes);
                $em->flush();
            }

            return new FOSView\View($image, $statusCode, array(
                'X-Location' => $this->generateUrl('newscoop_gimme_images_getimage', array(
                    'number' => $image->getId(),
                ), true))
            );
        }

        return $form;
    }
}
