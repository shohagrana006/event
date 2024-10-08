<?php

namespace App\Controller\Dashboard\Organizer;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use App\Service\AppServices;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class MainController extends Controller {


    private $tokenManager;

    public function __construct(CsrfTokenManagerInterface $tokenManager = null)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Route(name="index")
     */
    
    public function index(AppServices $services, TranslatorInterface $translator) {
        // Tickets Sales By Date Line Chart
        $datefrom = date_format(new \DateTime, "Y-m-01");
        $dateto = date_format(new \DateTime, "Y-m-t");
        $ordersQuantityByDate = $services->getOrders(array("organizer" => $this->getUser()->getOrganizer()->getSlug(), "ordersQuantityByDateStat" => true, "order" => "ASC", "datefrom" => $datefrom, "dateto" => $dateto))->getQuery()->getResult();
        foreach ($ordersQuantityByDate as $i => $resultArray) {
            $ordersQuantityByDate[$i] = array_values($resultArray);
            $ordersQuantityByDate[$i][1] = \DateTime::createFromFormat('Y-m-j', $ordersQuantityByDate[$i][1]);
            $ordersQuantityByDate[$i] = array_reverse($ordersQuantityByDate[$i]);
        }
        array_unshift($ordersQuantityByDate, [['label' => $translator->trans("Date"), 'type' => 'date'], ['label' => $translator->trans("Tickets sold"), 'type' => 'number']]);
        $ticketsSalesByDateLineChart = new LineChart();
        $ticketsSalesByDateLineChart->getData()->setArrayToDataTable($ordersQuantityByDate);
        $ticketsSalesByDateLineChart->getOptions()->setTitle($translator->trans("Tickets sales this month"));
        $ticketsSalesByDateLineChart->getOptions()->setCurveType('function');
        $ticketsSalesByDateLineChart->getOptions()->setLineWidth(2);
        $ticketsSalesByDateLineChart->getOptions()->getLegend()->setPosition('none');
        $params = [
            'organizer' => $this->getUser()->getOrganizer()->getSlug(),
            'count' => 1,
            'published' => 1,
            'elapsed' => 'all'
        ];
        $publishedEventCount =$services->getEvents($params)->getQuery()->getSingleScalarResult();

        

        return $this->render('Dashboard/Organizer/index.html.twig', [
                    'ticketsSalesByDateLineChart' => $ticketsSalesByDateLineChart,
                    'publishedEventCount' => $publishedEventCount
        ]);
    }

}
