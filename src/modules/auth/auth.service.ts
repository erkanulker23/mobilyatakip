import { Injectable, UnauthorizedException } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as bcrypt from 'bcrypt';
import { User } from '../../entities/user.entity';
import { UserRole } from '../../common/enums/role.enum';
import { LoginDto } from './dto/login.dto';
import { RegisterDto } from './dto/register.dto';

@Injectable()
export class AuthService {
  constructor(
    @InjectRepository(User)
    private userRepo: Repository<User>,
    private jwtService: JwtService,
  ) {}

  async validateUser(email: string, password: string): Promise<User | null> {
    const user = await this.userRepo.findOne({ where: { email } });
    if (user && (await bcrypt.compare(password, user.passwordHash))) {
      return user;
    }
    return null;
  }

  async login(dto: LoginDto) {
    const user = await this.validateUser(dto.email, dto.password);
    if (!user) throw new UnauthorizedException('E-posta veya şifre hatalı');
    if (!user.isActive) throw new UnauthorizedException('Hesap devre dışı');
    return { access_token: this.jwtService.sign({ sub: user.id, role: user.role }), user: { id: user.id, email: user.email, name: user.name, role: user.role } };
  }

  async register(dto: RegisterDto) {
    const existing = await this.userRepo.findOne({ where: { email: dto.email } });
    if (existing) throw new UnauthorizedException('Bu e-posta zaten kayıtlı');
    const hash = await bcrypt.hash(dto.password, 10);
    const user = this.userRepo.create({
      email: dto.email,
      passwordHash: hash,
      name: dto.name,
      role: (dto.role as UserRole) || UserRole.SATIS,
    });
    await this.userRepo.save(user);
    return { id: user.id, email: user.email, name: user.name, role: user.role };
  }

  async findById(id: string): Promise<User> {
    const user = await this.userRepo.findOne({ where: { id } });
    if (!user) throw new UnauthorizedException('Kullanıcı bulunamadı');
    return user;
  }

  async getProfile(id: string): Promise<Omit<User, 'passwordHash'>> {
    const user = await this.userRepo.findOne({ where: { id } });
    if (!user) throw new UnauthorizedException('Kullanıcı bulunamadı');
    const { passwordHash: _, ...rest } = user;
    return rest;
  }

  async findAll(): Promise<Omit<User, 'passwordHash'>[]> {
    const users = await this.userRepo.find({ order: { name: 'ASC' } });
    return users.map(({ passwordHash: _, ...u }) => u);
  }

  /** Servis kaydı atama için kullanıcı listesi (id, name). */
  async findAssignable(): Promise<{ id: string; name: string }[]> {
    const users = await this.userRepo.find({
      where: { isActive: true },
      select: ['id', 'name'],
      order: { name: 'ASC' },
    });
    return users.map((u) => ({ id: u.id, name: u.name }));
  }

  async updateRole(id: string, role: UserRole): Promise<Omit<User, 'passwordHash'>> {
    const user = await this.userRepo.findOne({ where: { id } });
    if (!user) throw new UnauthorizedException('Kullanıcı bulunamadı');
    user.role = role;
    await this.userRepo.save(user);
    const { passwordHash: _, ...rest } = user;
    return rest;
  }
}
