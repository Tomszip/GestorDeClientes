import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { Conversation, Message } from '../models/crm.models';

@Injectable({ providedIn: 'root' })
export class ConversacionService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  listar(): Observable<Conversation[]> {
    return this.http.get<Conversation[]>(`${this.apiUrl}/conversaciones/listar.php`, {
      headers: this.auth.getHeaders(),
    });
  }

  mensajes(conversacionId: number): Observable<Message[]> {
    return this.http.get<Message[]>(`${this.apiUrl}/conversaciones/mensajes.php`, {
      headers: this.auth.getHeaders(),
      params: { conversacionId: conversacionId.toString() },
    });
  }

  enviarMensaje(conversacionId: number, texto: string): Observable<Message> {
    return this.http.post<Message>(`${this.apiUrl}/conversaciones/enviar_mensaje.php`, { conversacionId, texto }, {
      headers: this.auth.getHeaders(),
    });
  }

  cambiarEstado(conversacionId: number, estado: 'nuevo' | 'en-curso' | 'cerrado'): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/conversaciones/cambiar_estado.php`, { conversacionId, estado }, {
      headers: this.auth.getHeaders(),
    });
  }

  crear(clienteId: number): Observable<Conversation> {
    return this.http.post<Conversation>(`${this.apiUrl}/conversaciones/crear.php`, { clienteId }, {
      headers: this.auth.getHeaders(),
    });
  }
}
