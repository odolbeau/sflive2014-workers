<?php

namespace SensioLabs\Bundle\Sflive2014Bundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="contact")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $data = array('email' => 'lyrixx@lyrixx.info', 'subject' => 'Hello SFLIVE', 'body' => 'coucou');

        $form = $this->createFormBuilder($data)
            ->add('email', 'email')
            ->add('subject')
            ->add('body', 'textarea', array('attr' => array('cols' => 80, 'rows' => 10)))
            ->add('submit', 'submit')
            ->getForm()
        ;

        if ($form->handleRequest($request)->isValid()) {
            $data = $form->getData();
            $message = $this->get('mailer')->createMessage()
                ->setFrom($data['email'])
                ->setTo($data['email'])
                ->setSubject($data['subject'])
                ->setBody($data['body'])
            ;
            $this->get('mailer')->send($message);

            return $this->redirect($this->generateUrl('contact'));
        }

        return array('form' => $form->createView());
    }
}
