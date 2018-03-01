<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 *  Mapbender repository controller
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 * @ManagerRoute("/repository")
 */
class RepositoryController extends Controller
{
    /**
     * Renders the layer service repository.
     *
     * @ManagerRoute("/{page}", defaults={ "page"=1 }, requirements={ "page"="\d+" })
     * @Method({ "GET" })
     * @Template
     */
    public function indexAction($page)
    {
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery("SELECT s FROM MapbenderCoreBundle:Source s ORDER BY s.id ASC");
        $sources = $query->getResult();

        $allowed_sources = array();
        foreach ($sources as $source) {
            if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('VIEW', $source)) {
                continue;
            }
            $allowed_sources[] = $source;
        }

        return array(
            'title' => 'Repository',
            'sources' => $allowed_sources,
            'oid' => $oid,
            'create_permission' => $securityContext->isGranted('CREATE', $oid)
        );
    }

    /**
     * Renders a list of importers
     *
     * @ManagerRoute("/new")
     * @Method({ "GET" })
     * @Template
     */
    public function newAction()
    {
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');

        if (false === $securityContext->isGranted('CREATE', $oid)) {
            throw new AccessDeniedException();
        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        return array(
            'managers' => $managers
        );
    }

    /**
     * Renders a list of importers
     *
     * @ManagerRoute("/create/{managertype}")
     * @Method({ "POST" })
     * @Template()
     */
    public function createAction($managertype)
    {
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');

        if (false === $securityContext->isGranted('CREATE', $oid)) {
            throw new AccessDeniedException();
        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$managertype];

        $path = array('_controller' => $manager['bundle'] . ":" . "Repository:create");
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * @ManagerRoute("/source/{sourceId}")
     * @Method({"GET"})
     * @Template
     */
    public function viewAction($sourceId)
    {
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        $path = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:view",
            "id" => $source->getId()
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * deletes a Source
     * @ManagerRoute("/source/{sourceId}/confirmdelete")
     * @Method({"GET"})
     * @Template("MapbenderManagerBundle:Repository:confirmdelete.html.twig")
     */
    public function confirmdeleteAction($sourceId)
    {
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);

        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        $securityContext = $this->get('security.context');

        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('DELETE', $source)) {
            throw new AccessDeniedException();
        }
        return array(
            'source' => $source
        );
    }

    /**
     * deletes a Source
     * @ManagerRoute("/source/{sourceId}/delete")
     * @Method({"POST"})
     */
    public function deleteAction($sourceId)
    {
        $source = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Source")->find($sourceId);

        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');

        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('DELETE', $source)) {
            throw new AccessDeniedException();
        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];

        $path = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:delete",
            "sourceId" => $source->getId()
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns a Source update form.
     *
     * @ManagerRoute("/source/{sourceId}/updateform")
     * @Method({"GET"})
     * @Template
     */
    public function updateformAction($sourceId)
    {
        $source = $this->getDoctrine()->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('EDIT', $source)) {
            throw new AccessDeniedException();
        }
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        return array(
            'manager' => $manager,
            'source' => $source
        );
    }

    /**
     * Updates a Source
     *
     * @ManagerRoute("/source/{sourceId}/update")
     * @Method({"POST"})
     * @Template
     */
    public function updateAction($sourceId)
    {
        $source = $this->getDoctrine()->getRepository("MapbenderCoreBundle:Source")->find($sourceId);
        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!$securityContext->isGranted('VIEW', $oid) && !$securityContext->isGranted('EDIT', $source)) {
            throw new AccessDeniedException();
        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$source->getManagertype()];
        $path = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:update",
            "sourceId" => $source->getId()
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instance/{instanceId}")
     */
    public function instanceAction($slug, $instanceId)
    {
        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);

        if (null === $sourceInst) {
            throw $this->createNotFoundException('Instance does not exist');
        }

        $securityContext = $this->get('security.context');
        $oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Source');
        if (!($securityContext->isGranted('VIEW', $sourceInst->getSource())
            || $securityContext->isGranted('VIEW', $oid))) {
            throw new AccessDeniedHttpException();
        }

        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];

        $path = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:instance",
            "slug" => $slug,
            "instanceId" => $sourceInst->getId()
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instance/{layersetId}/weight/{instanceId}")
     */
    public function instanceWeightAction($slug, $layersetId, $instanceId)
    {
        $number = $this->get("request")->get("number");
        $layersetId_new = $this->get("request")->get("new_layersetId");

        $instance = $this->getDoctrine()
            ->getRepository('MapbenderCoreBundle:SourceInstance')
            ->findOneById($instanceId);

        if (!$instance) {
            throw $this->createNotFoundException('The source instance id:"' . $instanceId . '" does not exist.');
        }
        /** @var SourceInstance $instance */
        if (intval($number) === $instance->getWeight() && $layersetId === $layersetId_new) {
            return new Response(json_encode(array(
                    'error' => '',
                    'result' => 'ok')), 200, array('Content-Type' => 'application/json'));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        if ($layersetId === $layersetId_new) {
            if ($instance->getLayerset()->moveInstance($instance, $number)) {
                $em->persist($instance->getLayerset());
                $em->flush();
            }
        } else {
            $oldLayerset = $instance->getLayerset();
            if ($oldLayerset->removeInstance($instance)) {
                $em->persist($oldLayerset);
            }
            /** @var Layerset|null $layerset_new */
            // @todo: it might really be null, need an earlier check
            $layerset_new = $this->getDoctrine()
                ->getRepository("MapbenderCoreBundle:Layerset")
                ->find($layersetId_new);
            $layerset_new->addInstanceAtOffset($instance, $number);
            $em->persist($layerset_new);
            $em->flush();
        }

        return new Response(json_encode(array(
                'error' => '',
                'result' => 'ok')), 200, array(
            'Content-Type' => 'application/json'));
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instance/{layersetId}/enabled/{instanceId}")
     * @Method({ "POST" })
     */
    public function instanceEnabledAction($slug, $layersetId, $instanceId)
    {
        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];

        $path = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:instanceenabled",
            "slug" => $slug,
            "layersetId" => $layersetId,
            "instanceId" => $sourceInst->getId()
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     *
     * @ManagerRoute("/application/{slug}/instanceLayer/{instanceId}/weight/{instLayerId}")
     */
    public function instanceLayerWeightAction($slug, $instanceId, $instLayerId)
    {
        $sourceInst = $this->getDoctrine()
            ->getRepository("MapbenderCoreBundle:SourceInstance")
            ->find($instanceId);
        $managers = $this->get('mapbender')->getRepositoryManagers();
        $manager = $managers[$sourceInst->getManagertype()];

        $path = array(
            '_controller' => $manager['bundle'] . ":" . "Repository:instancelayerpriority",
            "slug" => $slug,
            "instanceId" => $sourceInst->getId(),
            "instLayerId" => $instLayerId
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
