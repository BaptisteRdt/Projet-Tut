<?php

namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/document')]
class DocumentController extends AbstractController
{
    #[Route('/', name: 'document_index', methods: ['GET'])]
    public function index(DocumentRepository $documentRepository): Response
    {
        return $this->render('document/index.html.twig', [
            'documents' => $documentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'document_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $documentFile = $form->get("documentName")->getData();

            if ( $documentFile!= null){
                $name = md5(uniqid()).'.'.$documentFile->guessExtension();
                $documentFile->move('FileDocument', $name);
                $document->setDocumentName($name);
            }

            $entityManager->persist($document);
            $entityManager->flush();

            return $this->redirectToRoute('document_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/isPinned', name: 'document_pin', methods: ['GET', 'POST'])]
    public function pinned(Request $request, Document $document, EntityManagerInterface $entityManager)
    {
        if ($document->getIspinned()){
            $document->setIspinned(false);
        }else{
            $document->setIspinned(true);
        }
        $entityManager->flush();
        return $this->redirectToRoute('document_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'document_show', methods: ['GET'])]
    public function show(Document $document): Response
    {
        return $this->render('document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/edit', name: 'document_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            if($document->getDocumentName() != null){
                $filesystem = new Filesystem();
                $filesystem->remove("FileDocument/" .$document->getDocumentName());
            }

            $documentFile  = $form->get("documentName")->getData();
            if ($documentFile != null){
                $name = md5(uniqid()).'.'.$documentFile->guessExtension();
                $documentFile->move('FileDocument', $name);

                $document->setDocumentName($name);
            }


            $entityManager->flush();

            return $this->redirectToRoute('document_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'document_delete', methods: ['POST'])]
    public function delete(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            if($document->getDocumentName() != null){
                $filesystem = new Filesystem();
                $filesystem->remove("FileDocument/" .$document->getDocumentName());
            }
            $entityManager->remove($document);
            $entityManager->flush();
        }

        return $this->redirectToRoute('document_index', [], Response::HTTP_SEE_OTHER);
    }
}