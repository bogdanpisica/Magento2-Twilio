<?php
namespace Thecon\Twilio\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Twilio\Rest\ClientFactory;
use Magento\Sales\Api\OrderAddressRepositoryInterface;

class SalesOrderAfterSave implements ObserverInterface
{
    const TWILIO_SID = 'AC06511d08844c0d43292ee81e5ea53776';
    const TWILIO_TOKEN = '2d7653d2c4f7c2d969b255ef81bfb6ea';
    const TWILIO_NUMBER = '+18125778788';

    private $clientFactory;
    private $logger;
    private $observer;
    private $addressRepository;

    public function __construct(
        ClientFactory $clientFactory,
        LoggerInterface $logger,
        Observer $observer,
        OrderAddressRepositoryInterface $addressRepository
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->observer = $observer;
        $this->addressRepository = $addressRepository;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $ceva = $observer->getData('order');
        $idComanda = $ceva->getData('shipping_address_id');

        if($idComanda != 0) {

            $addressData = $this->addressRepository->get($idComanda);
            $telefon = $addressData->getData('telephone');
            file_put_contents("/home/tritonnexloc/public_html/aa.txt", $telefon);

            $client = $this->clientFactory->create([
                'username' => self::TWILIO_SID,
                'password' => self::TWILIO_TOKEN,
            ]);
            $params = [
                'from' => self::TWILIO_NUMBER,
                'body' => $this->getBody($observer),
            ];

            try {
                $client->messages->create($telefon, $params);
            } catch (\Exception $e) {
                $this->logger->critical('Error message', ['exception' => $e]);
            }
        }
    }

    public function getBody($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $ceva = $observer->getData('order');

        if ($order instanceof \Magento\Framework\Model\AbstractModel) {
            if($order->getState() != 'new') 
            {   
                if($order->getState() == 'canceled') {$status = 'Anulat';}
                if($order->getState() == 'complete') {$status = 'Finalizat';}
                if($order->getState() == 'processing') {$status = 'In procesare';}
                if($order->getState() == 'closed') {$status = 'Inchis';}

                $incrementId = $ceva->getData('increment_id');

                $result = "Buna ziua!" . PHP_EOL;
                $result .= PHP_EOL;
                $result .= "Statusul comenzii cu numarul #$incrementId a fost schimbat." . PHP_EOL;
                $result .= PHP_EOL;
                $result .= "Status actual: $status" . PHP_EOL;
                $result .= PHP_EOL;
                return $result;
            }
        }
    }
}